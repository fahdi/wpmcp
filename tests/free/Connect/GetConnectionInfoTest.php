<?php

namespace WPMCP\Tests\Free\Connect;

use WPMCP\Tools\Connect\Get_Connection_Info;

class GetConnectionInfoTest extends \WP_UnitTestCase
{
    public function test_endpoint_contains_the_site_host(): void
    {
        $out = (new Get_Connection_Info())->handle([]);

        $host = wp_parse_url(home_url(), PHP_URL_HOST);

        $this->assertStringContainsString($host, $out['endpoint']);
        $this->assertStringContainsString('/wp-json/mcp/wpmcp-server', $out['endpoint']);
    }

    public function test_claude_code_snippet_has_an_authorization_placeholder_and_no_real_secret(): void
    {
        $out = (new Get_Connection_Info())->handle([]);

        $snippet = $out['clients']['claude-code']['snippet'];

        $this->assertStringContainsString('Authorization', $snippet);
        $this->assertStringContainsString('Basic', $snippet);
        $this->assertStringContainsString($out['endpoint'], $snippet);

        // Only placeholder text stands in for the credential; no concrete
        // base64-looking Basic auth value is ever emitted.
        $this->assertStringContainsString('BASE64_OF_username:application-password', $snippet);
        $this->assertDoesNotMatchRegularExpression(
            '/Basic [A-Za-z0-9+\/]{8,}={0,2}/',
            (string) wp_json_encode($out)
        );
    }
}
