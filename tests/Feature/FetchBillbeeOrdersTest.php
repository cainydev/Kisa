<?php

use App\Facades\Billbee;
use BillbeeDe\BillbeeAPI\ClientInterface;
use BillbeeDe\BillbeeAPI\Endpoint\OrdersEndpoint;
use BillbeeDe\BillbeeAPI\Model\PagingInformation;
use BillbeeDe\BillbeeAPI\Response\GetOrdersResponse;

/**
 * Swap the Billbee facade for a stub whose orders() endpoint always answers
 * with the given response, so the command never touches the network.
 *
 * The SDK's Client is final and its ClientInterface only declares the HTTP
 * verbs, not orders(), so there is no seam to mock the endpoint accessor
 * directly. Instead we build a real OrdersEndpoint over a mocked transport.
 */
function fakeBillbeeOrders(GetOrdersResponse $response): void
{
    $transport = Mockery::mock(ClientInterface::class);
    $transport->shouldReceive('get')->andReturn($response);

    $endpoint = new OrdersEndpoint($transport);

    Billbee::swap(new class($endpoint)
    {
        public function __construct(private OrdersEndpoint $endpoint) {}

        public function orders(): OrdersEndpoint
        {
            return $this->endpoint;
        }
    });
}

describe('billbee:orders', function () {
    beforeEach(function () {
        fakeBillbeeOrders(new GetOrdersResponse(
            paging: new PagingInformation(page: 1, totalPages: 0, totalRows: 0, pageSize: 100),
            data: [],
        ));
    });

    it('exits cleanly when no orders match the window', function () {
        $this->artisan('billbee:orders', ['--since-minutes' => 15])
            ->expectsOutputToContain('No orders to sync.')
            ->assertSuccessful();
    });

    it('does not report a critical error for an empty result set', function () {
        $this->artisan('billbee:orders', ['--since-minutes' => 15])
            ->doesntExpectOutputToContain('Critical error')
            ->assertSuccessful();
    });
});
