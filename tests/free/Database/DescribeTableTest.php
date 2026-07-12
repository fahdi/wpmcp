<?php

namespace WPMCP\Tests\Free\Database;

use WPMCP\Tools\Database\Describe_Table;

class DescribeTableTest extends \WP_UnitTestCase
{
    public function test_describes_columns_of_a_known_table(): void
    {
        global $wpdb;

        $result = (new Describe_Table())->handle(['table' => $wpdb->options]);

        $this->assertSame($wpdb->options, $result['table']);
        $this->assertIsArray($result['columns']);
        $this->assertNotEmpty($result['columns']);

        $names = array_column($result['columns'], 'Field');
        $this->assertContains('option_name', $names);
        $this->assertContains('option_value', $names);
    }

    public function test_matches_table_name_case_insensitively(): void
    {
        global $wpdb;

        $result = (new Describe_Table())->handle(['table' => strtoupper($wpdb->options)]);

        $this->assertSame($wpdb->options, $result['table']);
    }

    public function test_rejects_unknown_table(): void
    {
        $this->expectException(\RuntimeException::class);
        (new Describe_Table())->handle(['table' => 'wp_this_table_does_not_exist']);
    }

    public function test_requires_table_argument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Describe_Table())->handle([]);
    }
}
