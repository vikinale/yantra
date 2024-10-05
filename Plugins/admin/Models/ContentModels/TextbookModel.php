<?php
namespace Plugins\admin\Models\ContentModels;

use Exception;
use System\Model;

class TextbookModel extends Model
{
    public function __construct()
    {
        parent::__construct('textbooks', 'ID');
    }

    /**
     * Create a new textbook.
     *
     * @throws Exception
     */
    public function create(string $title, string $author, int $classId, string $isbn = null, string $edition = null, string $publishedDate = null, int $numPages = null, string $lang = 'en', string $description = null, string $coverImage = null): int
    {
        $data = [
            'title' => $title,
            'author' => $author,
            'class_id' => $classId,
            'isbn' => $isbn,
            'edition' => $edition,
            'published_date' => $publishedDate,
            'num_pages' => $numPages,
            'lang' => $lang,
            'description' => $description,
            'cover_image' => $coverImage,
        ];

        return $this->insert($data);
    }
}
