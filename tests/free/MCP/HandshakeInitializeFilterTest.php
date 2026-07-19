<?php

namespace WPMCP\Tests\Free\MCP;

use WPMCP\MCP\Handshake_Instructions;

/**
 * The wiring half of issue #80: wpmcp hooks the MCP Adapter's documented
 * mcp_adapter_initialize_response filter and swaps the initialize result's
 * `instructions` for Handshake_Instructions::build(). The adapter is not
 * installable in this harness (its InitializeResult DTO ships with the
 * mcp-adapter plugin), so the filter callback is duck-typed against the
 * DTO's documented toArray()/fromArray() contract and exercised here with a
 * stand-in implementing that exact contract.
 */
class HandshakeInitializeFilterTest extends \WP_UnitTestCase
{
    public static function wpSetUpBeforeClass(): void
    {
        if (0 === did_action('wp_abilities_api_init')) {
            do_action('wp_abilities_api_init');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        delete_option(Handshake_Instructions::OPTION);
        $editor = self::factory()->user->create(['role' => 'editor']);
        wp_set_current_user($editor);
    }

    protected function tearDown(): void
    {
        delete_option(Handshake_Instructions::OPTION);
        parent::tearDown();
    }

    public function test_the_initialize_response_filter_is_hooked_at_boot(): void
    {
        $this->assertNotFalse(
            has_filter('mcp_adapter_initialize_response'),
            'Plugin::boot() must hook mcp_adapter_initialize_response so every initialize handshake carries the instructions.'
        );
    }

    public function test_filter_replaces_instructions_and_preserves_the_rest_of_the_result(): void
    {
        update_option(Handshake_Instructions::OPTION, 'Save drafts only.');

        $result = Fake_Initialize_Result::fromArray([
            'protocolVersion' => '2025-06-18',
            'capabilities'    => ['tools' => ['listChanged' => false]],
            'serverInfo'      => ['name' => 'wpmcp-server', 'version' => '1.0'],
            'instructions'    => 'Adapter default description.',
        ]);

        $filtered = (new Handshake_Instructions())->filter_initialize($result, null);

        $this->assertInstanceOf(Fake_Initialize_Result::class, $filtered);

        $data = $filtered->toArray();
        $this->assertSame((new Handshake_Instructions())->build(), $data['instructions']);
        $this->assertStringContainsString('Save drafts only.', $data['instructions']);
        $this->assertSame('2025-06-18', $data['protocolVersion']);
        $this->assertSame(['name' => 'wpmcp-server', 'version' => '1.0'], $data['serverInfo']);
    }

    public function test_filtered_instructions_come_from_the_applied_filter_end_to_end(): void
    {
        $result = Fake_Initialize_Result::fromArray(['instructions' => 'stock']);

        $filtered = apply_filters('mcp_adapter_initialize_response', $result, null);

        $this->assertSame((new Handshake_Instructions())->build(), $filtered->toArray()['instructions']);
    }

    public function test_a_value_without_the_dto_contract_passes_through_unchanged(): void
    {
        $plain_array = ['instructions' => 'untouched'];
        $this->assertSame($plain_array, (new Handshake_Instructions())->filter_initialize($plain_array, null));

        $foreign = new \stdClass();
        $this->assertSame($foreign, (new Handshake_Instructions())->filter_initialize($foreign, null));
    }
}

/**
 * Stand-in for \WP\McpSchema\Common\Protocol\DTO\InitializeResult, matching
 * the toArray()/fromArray() round-trip contract the adapter's own filter
 * docblock instructs integrators to use.
 */
class Fake_Initialize_Result
{
    private array $data;

    private function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
