<?php

namespace App\Mcp\Concerns;

use App\Models\BioInspector;
use App\Models\Herb;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Support\Str;

/**
 * Shared lookup helpers so MCP tools can accept the human-friendly identifiers
 * a chatbot user would actually type (a herb name, a supplier shortname, an
 * öko-code) instead of forcing numeric IDs. Each resolver returns the model or
 * null; tools turn null into a clear, actionable error message.
 */
trait ResolvesEntities
{
    /**
     * Resolve a herb by numeric id, exact name/fullname, or fuzzy contains.
     */
    protected function resolveHerb(int|string $identifier): ?Herb
    {
        if (is_numeric($identifier)) {
            return Herb::find((int) $identifier);
        }

        $needle = Str::lower(trim((string) $identifier));

        $herbs = Herb::query()->get();

        return $herbs->first(fn (Herb $h): bool => in_array($needle, [Str::lower((string) $h->name), Str::lower((string) $h->fullname)], true))
            ?? $herbs->first(fn (Herb $h): bool => str_contains(Str::lower((string) $h->name), $needle) || str_contains(Str::lower((string) $h->fullname), $needle));
    }

    /**
     * Resolve a supplier by numeric id, exact shortname/company, or fuzzy contains.
     */
    protected function resolveSupplier(int|string $identifier): ?Supplier
    {
        if (is_numeric($identifier)) {
            return Supplier::find((int) $identifier);
        }

        $needle = Str::lower(trim((string) $identifier));

        $suppliers = Supplier::query()->get();

        return $suppliers->first(fn (Supplier $s): bool => in_array($needle, [Str::lower((string) $s->shortname), Str::lower((string) $s->company)], true))
            ?? $suppliers->first(fn (Supplier $s): bool => str_contains(Str::lower((string) $s->shortname), $needle) || str_contains(Str::lower((string) $s->company), $needle));
    }

    /**
     * Resolve a product by numeric id, exact name, or fuzzy contains.
     */
    protected function resolveProduct(int|string $identifier): ?Product
    {
        if (is_numeric($identifier)) {
            return Product::find((int) $identifier);
        }

        $needle = Str::lower(trim((string) $identifier));

        $products = Product::query()->get();

        return $products->first(fn (Product $p): bool => Str::lower((string) $p->name) === $needle)
            ?? $products->first(fn (Product $p): bool => str_contains(Str::lower((string) $p->name), $needle));
    }

    /**
     * Resolve a control body (BioInspector) by its öko-code (label) or company.
     */
    protected function resolveBioInspector(string $identifier): ?BioInspector
    {
        $needle = Str::lower(trim($identifier));

        $inspectors = BioInspector::query()->get();

        return $inspectors->first(fn (BioInspector $b): bool => Str::lower((string) $b->label) === $needle)
            ?? $inspectors->first(fn (BioInspector $b): bool => str_contains(Str::lower((string) $b->company), $needle));
    }
}
