<?php
declare(strict_types=1);

function yantra_render_section_title(string $title, string $desc = ''): void
{
    $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $desc  = htmlspecialchars($desc, ENT_QUOTES, 'UTF-8');

    echo <<<HTML
    <div class="section-title text-center mb-5">
        <h2>{$title}</h2>
        <p>{$desc}</p>
    </div>
    HTML;
}
add_action('section_title', 'yantra_render_section_title', 10, null, 2);
