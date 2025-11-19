<?php
namespace System\Utilities\Pagination;

use PDO;
use InvalidArgumentException;
use DateTimeImmutable;

/**
 * Simple opaque cursor for multi-column keyset pagination.
 * Encodes JSON: { "v": [val1,val2,...], "t": "<timestamp>" } base64url.
 */
class Cursor
{
    public array $values;
    public string $timestamp;

    public function __construct(array $values, ?string $timestamp = null)
    {
        $this->values = $values;
        $this->timestamp = $timestamp ?? (new DateTimeImmutable())->format(DATE_ATOM);
    }

    public function encode(): string
    {
        $json = json_encode(['v' => $this->values, 't' => $this->timestamp]);
        return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    }

    public static function decode(string $cursor): ?Cursor
    {
        $pad = 4 - (strlen($cursor) % 4);
        if ($pad < 4) $cursor .= str_repeat('=', $pad);
        $raw = base64_decode(strtr($cursor, '-_', '+/'));
        if ($raw === false) return null;
        $arr = json_decode($raw, true);
        if (!is_array($arr) || !isset($arr['v']) || !is_array($arr['v'])) return null;
        return new Cursor($arr['v'], $arr['t'] ?? null);
    }
}

/**
 * PdoCursorPaginator
 *
 * Minimal API:
 *   paginateFromSql(PDO $pdo, string $sql, array $bindings, array $orderColumns, int $limit = 25, ?string $after = null, ?string $before = null, string $idColumn = 'id', callable $linkBuilder = null)
 *
 * - $sql should be the FROM/SELECT statement you want to page. Prefer wrapping complex queries as subquery: "(SELECT ...) AS t"
 * - $bindings are positional ? bindings matching $sql
 * - $orderColumns is an ordered array like: [['col'=>'created_at','dir'=>'DESC'], ['col'=>'score','dir'=>'DESC']]
 * - All directions must be uniform (all ASC or all DESC).
 */
class PdoCursorPaginator
{
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Paginate using keyset multi-column logic.
     *
     * @param PDO $pdo
     * @param string $sql SELECT ... FROM ... WHERE ...  (if complex, wrap as "(SELECT ...) AS t")
     * @param array $bindings positional bindings for $sql
     * @param array $orderColumns array of ['col'=>'name','dir'=>'ASC'|'DESC'] - at least 1 item
     * @param int $limit
     * @param string|null $after opaque cursor
     * @param string|null $before opaque cursor
     * @param string $idColumn tie-breaker column, must be included in orderColumns or will be appended
     * @param callable|null $linkBuilder function(array $queryParams): string
     * @return array ['data'=>[], 'meta'=>[], 'links'=>[]]
     */
    public function paginateFromSql(
        string $sql,
        array $bindings,
        array $orderColumns,
        int $limit = 25,
        ?string $after = null,
        ?string $before = null,
        string $idColumn = 'id',
        ?callable $linkBuilder = null
    ): array {
        if (empty($orderColumns)) {
            throw new InvalidArgumentException("orderColumns is required");
        }

        // normalize directions & validate column names
        $firstDir = strtoupper($orderColumns[0]['dir'] ?? 'DESC');
        $normalized = [];
        foreach ($orderColumns as $o) {
            $col = $o['col'] ?? null;
            $dir = strtoupper($o['dir'] ?? $firstDir);
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $col)) {
                throw new InvalidArgumentException("Invalid order column: {$col}");
            }
            $dir = ($dir === 'ASC') ? 'ASC' : 'DESC';
            $normalized[] = ['col' => $col, 'dir' => $dir];
            if ($dir !== $firstDir) {
                throw new InvalidArgumentException("All order directions must be the same");
            }
        }
        $orderColumns = $normalized;

        // ensure tie-breaker as last column
        $lastCol = end($orderColumns)['col'];
        if ($lastCol !== $idColumn) {
            $orderColumns[] = ['col' => $idColumn, 'dir' => $firstDir];
        }

        $limit = max(1, min($limit, 100));

        // Cursor parse
        $cursorObj = null;
        $isAfter = true; // true = after (next page), false = before (prev page)
        if ($after !== null) {
            $cursorObj = Cursor::decode($after);
            $isAfter = true;
        } elseif ($before !== null) {
            $cursorObj = Cursor::decode($before);
            $isAfter = false;
        }

        $cursorValues = null;
        if ($cursorObj !== null) {
            $cursorValues = $cursorObj->values;
            if (!is_array($cursorValues) || count($cursorValues) !== count($orderColumns)) {
                throw new InvalidArgumentException("Cursor values must match order columns count");
            }
        }

        // Compose ordering SQL
        $orderSqlParts = array_map(fn($o) => "{$o['col']} {$o['dir']}", $orderColumns);
        $orderSql = implode(', ', $orderSqlParts);

        // Build keyset SQL if cursor present
        $whereAddition = '';
        $keysetParams = [];
        if ($cursorValues !== null) {
            $dir = $orderColumns[0]['dir']; // uniform dir
            $baseOp = ($dir === 'DESC') ? '<' : '>';
            $op = $isAfter ? $baseOp : ($baseOp === '<' ? '>' : '<');

            // create lexicographic OR-chain:
            // (c1 op ?) OR (c1 = ? AND c2 op ?) OR (c1 = ? AND c2 = ? AND c3 op ?)
            $cols = array_column($orderColumns, 'col');
            $lexParts = [];
            for ($depth = 1; $depth <= count($cols); $depth++) {
                $conds = [];
                for ($i = 0; $i < $depth - 1; $i++) {
                    $conds[] = "{$cols[$i]} = ?";
                    $keysetParams[] = $cursorValues[$i];
                }
                $conds[] = "{$cols[$depth - 1]} {$op} ?";
                $keysetParams[] = $cursorValues[$depth - 1];
                $lexParts[] = '(' . implode(' AND ', $conds) . ')';
            }
            $whereAddition = ' AND (' . implode(' OR ', $lexParts) . ')';
        }

        // Final SQL: wrap original SQL as subquery if it is not already a subquery/table-like.
        // Caller should prefer passing "(SELECT ...) AS t" for complex queries. We'll not try to rewrite SQL;
        // we will append "ORDER BY ... LIMIT ?" to the provided SQL.
        // Expectation: $sql already contains WHERE clauses if needed.
        $finalSql = rtrim($sql);
        // If $finalSql does not contain "ORDER BY" or "LIMIT", we'll append; else caller must ensure final SQL is safe.
        // We'll insert keyset addition before ORDER BY if an ORDER BY exists in $finalSql.
        $hasOrderBy = (stripos($finalSql, ' ORDER BY ') !== false);

        if ($hasOrderBy) {
            // naive insertion: find position of last ORDER BY and append LIMIT after it.
            // But we need to add keyset condition into WHERE — so require caller to have a WHERE clause or we'll add WHERE 1=1
            if (stripos($finalSql, ' WHERE ') === false) {
                $finalSql .= ' WHERE 1=1';
            }
            $finalSql .= $whereAddition . " ORDER BY {$orderSql} LIMIT ?";
            $execBindings = array_merge($bindings, $keysetParams, [$limit + 1]);
        } else {
            // ensure WHERE exists
            if (stripos($finalSql, ' WHERE ') === false) {
                $finalSql .= ' WHERE 1=1';
            }
            $finalSql .= $whereAddition . " ORDER BY {$orderSql} LIMIT ?";
            $execBindings = array_merge($bindings, $keysetParams, [$limit + 1]);
        }

        // Execute
        $stmt = $this->pdo->prepare($finalSql);
        $stmt->execute($execBindings);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $hasMore = count($rows) > $limit;
        if ($hasMore) array_pop($rows);

        // Build cursors
        $nextCursor = null;
        $prevCursor = null;
        if (count($rows) > 0) {
            $first = reset($rows);
            $last = end($rows);
            $firstVals = [];
            $lastVals = [];
            foreach ($orderColumns as $o) {
                $col = $o['col'];
                $firstVals[] = $first[$col] ?? null;
                $lastVals[] = $last[$col] ?? null;
            }
            $nextCursor = (new Cursor($lastVals))->encode();
            $prevCursor = (new Cursor($firstVals))->encode();
        }

        $meta = [
            'limit' => $limit,
            'has_more' => $hasMore,
            'next_cursor' => $nextCursor,
            'prev_cursor' => $prevCursor,
            'order_by' => array_column($orderColumns, 'col'),
            'direction' => $orderColumns[0]['dir'],
        ];

        $links = [
            'next' => $linkBuilder && $meta['next_cursor'] ? call_user_func($linkBuilder, ['after' => $meta['next_cursor']]) : null,
            'prev' => $linkBuilder && $meta['prev_cursor'] ? call_user_func($linkBuilder, ['before' => $meta['prev_cursor']]) : null,
        ];

        return ['data' => $rows, 'meta' => $meta, 'links' => $links];
    }
}