<?php

namespace WPMCP\Tests\Free\Auth;

use WPMCP\Auth\Protected_Resource_Metadata;

/**
 * Protected_Resource_Metadata builds the RFC 9728 OAuth 2.0 Protected
 * Resource Metadata document served at /.well-known/oauth-protected-resource.
 * This is the document the MCP authorization spec has clients fetch to
 * discover which authorization server protects this resource (the site's
 * MCP/Abilities surface), per issue #43.
 */
class ProtectedResourceMetadataTest extends \WP_UnitTestCase
{
    public function test_document_contains_the_resource_identifier(): void
    {
        $doc = Protected_Resource_Metadata::build('https://example.com');

        $this->assertSame('https://example.com', $doc['resource']);
    }

    public function test_document_points_at_the_authorization_server(): void
    {
        $doc = Protected_Resource_Metadata::build('https://example.com');

        $this->assertSame(['https://example.com'], $doc['authorization_servers']);
    }

    public function test_bearer_is_the_only_supported_auth_method(): void
    {
        $doc = Protected_Resource_Metadata::build('https://example.com');

        $this->assertSame(['header'], $doc['bearer_methods_supported']);
    }
}
