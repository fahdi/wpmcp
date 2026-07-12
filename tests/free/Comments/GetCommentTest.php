<?php

namespace WPMCP\Tests\Free\Comments;

use WPMCP\Tools\Comments\Get_Comment;

class GetCommentTest extends \WP_UnitTestCase
{
    private array $created = [];

    protected function tearDown(): void
    {
        foreach ($this->created as $id) {
            wp_delete_comment($id, true);
        }
        $this->created = [];
        parent::tearDown();
    }

    public function test_get_returns_comment_detail(): void
    {
        $post_id = self::factory()->post->create();
        $id      = self::factory()->comment->create([
            'comment_post_ID'      => $post_id,
            'comment_content'      => 'Body text',
            'comment_author'       => 'Ada',
            'comment_author_email' => 'ada@example.com',
            'comment_approved'     => '0',
        ]);
        $this->created[] = $id;

        $out = (new Get_Comment())->handle(['id' => $id]);

        $this->assertSame($id, $out['id']);
        $this->assertSame($post_id, $out['post_id']);
        $this->assertSame('Body text', $out['content']);
        $this->assertSame('Ada', $out['author']);
        $this->assertSame('unapproved', $out['status']);
    }

    public function test_get_requires_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Get_Comment())->handle([]);
    }

    public function test_get_not_found_throws(): void
    {
        $this->expectException(\RuntimeException::class);
        (new Get_Comment())->handle(['id' => 999999]);
    }
}
