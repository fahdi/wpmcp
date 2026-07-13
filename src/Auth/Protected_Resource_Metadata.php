<?php

namespace WPMCP\Auth;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Builds the RFC 9728 OAuth 2.0 Protected Resource Metadata document, served
 * at /.well-known/oauth-protected-resource by
 * Metadata_Endpoint::protected_resource(). This is the document the MCP
 * authorization spec references so a client can discover which
 * authorization server protects a given resource; here the resource
 * (the site's own MCP/Abilities surface) and the authorization server are
 * the same origin, since this plugin is both in one.
 */
class Protected_Resource_Metadata
{
    public static function build(string $resource): array
    {
        return [
            'resource'                  => $resource,
            'authorization_servers'     => [$resource],
            'bearer_methods_supported'  => ['header'],
        ];
    }
}
