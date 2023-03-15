<?php

declare(strict_types=1);

use App\Orchid\Screens\Role\RoleEditScreen;
use App\Orchid\Screens\Role\RoleListScreen;
use App\Orchid\Screens\User\UserEditScreen;
use App\Orchid\Screens\User\UserListScreen;
use App\Orchid\Screens\User\UserProfileScreen;

use App\Orchid\Screens\Information\{WelcomeScreen, DashboardScreen, SettingsScreen};
use App\Orchid\Screens\Meta\{SupplierScreen, SupplierEditScreen, InspectorScreen, InspectorEditScreen, ProductTypeScreen, ProductTypeEditScreen};
use App\Orchid\Screens\Abfuellen\{AbfuellenScreen, AbfuellenEditScreen, AbfuellenRecipeScreen};
use App\Orchid\Screens\Delivery\{DeliveryScreen, DeliveryEditScreen};
use App\Orchid\Screens\Bag\{BagScreen, BagEditScreen};
use App\Orchid\Screens\Herb\{HerbScreen, HerbStatisticScreen, HerbEditScreen};
use App\Orchid\Screens\Product\{ProductScreen, ProductEditScreen, ProductStatisticsScreen};
use App\Orchid\Screens\Products\ProductStatisticsScreen as ProductsProductStatisticsScreen;
use App\Orchid\Screens\Restock\RestockScreen;
use Illuminate\Support\Facades\Route;
use Tabuna\Breadcrumbs\Trail;

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the need "dashboard" middleware group. Now create something great!
|
*/

// Platform > Welcome
Route::screen('welcome', WelcomeScreen::class)
    ->name('platform.main')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.index')
            ->push('Willkommen');
    });

// Platform > Info > Dashboard
Route::screen('dashboard', DashboardScreen::class)
    ->name('platform.info.dashboard')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.index')
            ->push('Dashboard');
    });

// Platform > Info > Settings
Route::screen('settings', SettingsScreen::class)
    ->name('platform.info.settings')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.index')
            ->push('Einstellungen');
    });

// Platform > Bestand > Abfüllen
Route::screen('bottle', AbfuellenScreen::class)
    ->name('platform.bottle')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.index')
            ->push('Abfüllungen');
    });
Route::screen('bottle/edit/{bottle?}', AbfuellenEditScreen::class)
    ->name('platform.bottle.edit')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.bottle')
            ->push('Erstellen oder Bearbeiten');
    });
Route::screen('bottle/recipe/{bottle?}', AbfuellenRecipeScreen::class)
    ->name('platform.bottle.recipe')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.bottle')
            ->push('Rezept');
    });

// Platform > Bestand > Lieferungen
Route::screen('deliveries', DeliveryScreen::class)
    ->name('platform.deliveries')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.index')
            ->push('Lieferungen');
    });
Route::screen('deliveries/edit/{delivery?}', DeliveryEditScreen::class)
    ->name('platform.deliveries.edit')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.deliveries')
            ->push('Erstellen oder Bearbeiten');
    });

// Platform > Bestand > Bag
Route::screen('bags', BagScreen::class)
    ->name('platform.bags')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.index')
            ->push('Säcke');
    });
Route::screen('bags/edit/{bag?}', BagEditScreen::class)
    ->name('platform.bags.edit')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.bags')
            ->push('Erstellen oder Bearbeiten');
    });

// Platform > Bestand > Restock
Route::screen('bags.restock', RestockScreen::class)
    ->name('platform.bags.restock')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.index')
            ->push('Nachbestellen');
    });

// Platform > Produkte > Rohstoffe
Route::screen('herbs', HerbScreen::class)
    ->name('platform.herbs')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.index')
            ->push('Rohstoffe');
    });
Route::screen('herbs/edit/{herb?}', HerbEditScreen::class)
    ->name('platform.herbs.edit')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.herbs')
            ->push('Erstellen oder Bearbeiten');
    });
Route::screen('herbs/statistics/{herb}', HerbStatisticScreen::class)
    ->name('platform.herbs.statistics')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.herbs')
            ->push('Statistiken');
    });

// Platform > Produkte > Endprodukte
Route::screen('products', ProductScreen::class)
    ->name('platform.products')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.index')
            ->push('Endprodukte');
    });
Route::screen('products/edit/{product?}', ProductEditScreen::class)
    ->name('platform.products.edit')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.products')
            ->push('Erstellen oder Bearbeiten');
    });
Route::screen('products/statistics/{product}', ProductStatisticsScreen::class)
    ->name('platform.products.statistics')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.products')
            ->push('Statistiken');
    });

// Platform > Meta > ProductType
Route::screen('product-type', ProductTypeScreen::class)
    ->name('platform.meta.producttype')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.index')
            ->push('Produktgruppen');
    });
Route::screen('product-type/edit/{type?}', ProductTypeEditScreen::class)
    ->name('platform.meta.producttype.edit')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.meta.producttype')
            ->push('Erstellen oder Bearbeiten');
    });

// Platform > Meta > Supplier
Route::screen('supplier', SupplierScreen::class)
    ->name('platform.meta.supplier')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.index')
            ->push('Lieferanten');
    });
Route::screen('supplier/edit/{supplier?}', SupplierEditScreen::class)
    ->name('platform.meta.supplier.edit')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.meta.supplier')
            ->push('Erstellen oder Bearbeiten');
    });

// Platform > Meta > BioInspector
Route::screen('inspector', InspectorScreen::class)
    ->name('platform.meta.inspector')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.index')
            ->push('Bio-Kontrollstellen');
    });

Route::screen('inspector/edit/{inspector?}', InspectorEditScreen::class)
    ->name('platform.meta.inspector.edit')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.meta.inspector')
            ->push('Erstellen oder Bearbeiten');
    });

// Platform > Profile
Route::screen('profile', UserProfileScreen::class)
    ->name('platform.profile')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.index')
            ->push(__('Profile'), route('platform.profile'));
    });

// Platform > System > Users
Route::screen('users/{user}/edit', UserEditScreen::class)
    ->name('platform.systems.users.edit')
    ->breadcrumbs(function (Trail $trail, $user) {
        return $trail
            ->parent('platform.systems.users')
            ->push(__('User'), route('platform.systems.users.edit', $user));
    });

// Platform > System > Users > Create
Route::screen('users/create', UserEditScreen::class)
    ->name('platform.systems.users.create')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.systems.users')
            ->push(__('Create'), route('platform.systems.users.create'));
    });

// Platform > System > Users > User
Route::screen('users', UserListScreen::class)
    ->name('platform.systems.users')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.index')
            ->push(__('Users'), route('platform.systems.users'));
    });

// Platform > System > Roles > Role
Route::screen('roles/{role}/edit', RoleEditScreen::class)
    ->name('platform.systems.roles.edit')
    ->breadcrumbs(function (Trail $trail, $role) {
        return $trail
            ->parent('platform.systems.roles')
            ->push(__('Role'), route('platform.systems.roles.edit', $role));
    });

// Platform > System > Roles > Create
Route::screen('roles/create', RoleEditScreen::class)
    ->name('platform.systems.roles.create')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.systems.roles')
            ->push(__('Create'), route('platform.systems.roles.create'));
    });

// Platform > System > Roles
Route::screen('roles', RoleListScreen::class)
    ->name('platform.systems.roles')
    ->breadcrumbs(function (Trail $trail) {
        return $trail
            ->parent('platform.index')
            ->push(__('Roles'), route('platform.systems.roles'));
    });
