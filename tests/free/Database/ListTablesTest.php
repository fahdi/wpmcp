<?php

namespace WPMCP\Tests\Free\Database;

use WPMCP\Tools\Database\List_Tables;

class ListTablesTest extends \WP_UnitTestCase
{
    public function test_lists_known_core_tables_with_row_and_size_info(): void
    {
        global $wpdb;

        $result = (new List_Tables())->handle([]);

        $this->assertArrayHasKey('tables', $result);
        $this->assertIsArray($result['tables']);

        $names = array_column($result['tables'], 'table');
        $this->assertContains($wpdb->options, $names);
        $this->assertContains($wpdb->posts, $names);

        foreach ($result['tables'] as $row) {
            $this->assertArrayHasKey('table', $row);
            $this->assertArrayHasKey('rows', $row);
            $this->assertArrayHasKey('size_bytes', $row);
            $this->assertIsInt($row['rows']);
            $this->assertIsInt($row['size_bytes']);
        }
    }
}
