<?php

namespace WPMCP\Tools\Comments;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only: return the detail for one comment as a safe summary row (id,
 * post, parent, author fields, content, status, date). Never touches
 * Safe_Mutation. Uses the same Comment_View formatting as List_Comments so
 * status labels are consistent across the read tools.
 */
class Get_Comment
{
    public function handle(array $args): array
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            throw new \InvalidArgumentException('A comment id is required.');
        }

        $comment = get_comment($id);
        if (! $comment) {
            throw new \RuntimeException('Comment not found.');
        }

        return Comment_View::row($comment);
    }
}
