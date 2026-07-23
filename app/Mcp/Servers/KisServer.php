<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\AddBagsToDeliveryTool;
use App\Mcp\Tools\CreateCertificateTool;
use App\Mcp\Tools\CreateDeliveryTool;
use App\Mcp\Tools\CreateHerbTool;
use App\Mcp\Tools\CreateProductTool;
use App\Mcp\Tools\CreateSupplierTool;
use App\Mcp\Tools\CreateVariantTool;
use App\Mcp\Tools\FindBagByChargeTool;
use App\Mcp\Tools\GetDeliveryTool;
use App\Mcp\Tools\GetHerbTool;
use App\Mcp\Tools\GetProductTool;
use App\Mcp\Tools\GetSupplierTool;
use App\Mcp\Tools\GetVariantTool;
use App\Mcp\Tools\HerbsToReorderTool;
use App\Mcp\Tools\ListDeliveriesTool;
use App\Mcp\Tools\ListHerbsTool;
use App\Mcp\Tools\ListProductsTool;
use App\Mcp\Tools\ListSuppliersTool;
use App\Mcp\Tools\SetProductRecipeTool;
use App\Mcp\Tools\StockOverviewTool;
use App\Mcp\Tools\TraceChargeTool;
use App\Mcp\Tools\VariantsToProduceTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('KISA')]
#[Version('0.1.0')]
#[Instructions(<<<'TXT'
KISA is the internal Kräuterinformationssystem of Kräuter & Wege, an EU-organic
herbal-tea filling operation. Use these tools to read and manage raw materials
(herbs), suppliers and their organic certificates, deliveries of herb charges,
products and their recipes, product variants, and to answer stock/traceability
questions.

Conventions:
- You may refer to herbs, suppliers and products by name/shortname, not just id.
- Control bodies are identified by their öko-code (e.g. "DE-ÖKO-001").
- Amounts are in grams unless stated otherwise.
- Organic certificates are frozen onto deliveries as an audit snapshot; creating
  a delivery resolves and attaches the supplier's valid certificate automatically.
- Active bottling/filling is intentionally not writable through this server.
TXT)]
class KisServer extends Server
{
    protected array $tools = [
        // Herbs
        ListHerbsTool::class,
        GetHerbTool::class,
        CreateHerbTool::class,
        HerbsToReorderTool::class,
        // Suppliers & certificates
        ListSuppliersTool::class,
        GetSupplierTool::class,
        CreateSupplierTool::class,
        CreateCertificateTool::class,
        // Deliveries
        ListDeliveriesTool::class,
        GetDeliveryTool::class,
        CreateDeliveryTool::class,
        AddBagsToDeliveryTool::class,
        // Products, recipes, variants
        ListProductsTool::class,
        GetProductTool::class,
        CreateProductTool::class,
        SetProductRecipeTool::class,
        GetVariantTool::class,
        CreateVariantTool::class,
        VariantsToProduceTool::class,
        // Bags & traceability
        FindBagByChargeTool::class,
        TraceChargeTool::class,
        // Cross-entity analysis
        StockOverviewTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
