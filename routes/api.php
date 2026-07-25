<?php

use App\Http\Controllers\api\admin\AnalyticsController;
use App\Http\Controllers\api\admin\ApplicationSettingController;
use App\Http\Controllers\api\admin\auth\AdminAuthController;
use App\Http\Controllers\api\admin\BannerController;
use App\Http\Controllers\api\admin\BlogController;
use App\Http\Controllers\api\admin\BrandController;
use App\Http\Controllers\api\admin\CategoreyController;
use App\Http\Controllers\api\admin\ContactUsController;
use App\Http\Controllers\api\admin\CouponController;
use App\Http\Controllers\api\admin\DashboardController;
use App\Http\Controllers\api\admin\DatabaseSettingController;
use App\Http\Controllers\api\admin\DealController;
use App\Http\Controllers\api\admin\EmailTemplateController;
use App\Http\Controllers\api\admin\FeatureController;
use App\Http\Controllers\api\admin\ImportExportController;
use App\Http\Controllers\api\admin\InventoryController;
use App\Http\Controllers\api\admin\NavLinkController;
use App\Http\Controllers\api\admin\OrderController as AdminOrderController;
use App\Http\Controllers\api\admin\PermissionController;
use App\Http\Controllers\api\admin\product\ProductController;
use App\Http\Controllers\api\admin\ReturnController as AdminReturnController;
use App\Http\Controllers\api\admin\ShipmentController;
use App\Http\Controllers\api\admin\ShippingMethodController;
use App\Http\Controllers\api\admin\ShippingRateController;
use App\Http\Controllers\api\admin\ShippingZoneController;
use App\Http\Controllers\api\admin\SiteSettingController;
use App\Http\Controllers\api\admin\TaxRuleController;
use App\Http\Controllers\api\admin\TestimonialController;
use App\Http\Controllers\api\admin\TicketController;
use App\Http\Controllers\api\admin\TrustItemController;
use App\Http\Controllers\api\admin\UserController;
use App\Http\Controllers\api\auth\AuthController;
use App\Http\Controllers\api\auth\GoogleAuthController;
use App\Http\Controllers\api\auth\ProfileController;
use App\Http\Controllers\api\auth\RegisterController;
use App\Http\Controllers\api\auth\WalletController;
use App\Http\Controllers\api\home\AddressController;
use App\Http\Controllers\api\home\BlogController as HomeBlogController;
use App\Http\Controllers\api\home\BrandController as HomeBrandController;
use App\Http\Controllers\api\home\CartController;
use App\Http\Controllers\api\home\CategoryController as HomeCategoryController;
use App\Http\Controllers\api\home\CheckoutController;
use App\Http\Controllers\api\home\ContactUsController as HomeContactUsController;
use App\Http\Controllers\api\home\HomeContentController;
use App\Http\Controllers\api\home\HomePalleteController;
use App\Http\Controllers\api\home\InvoiceController;
use App\Http\Controllers\api\home\InvoiceController as HomeInvoiceController;
use App\Http\Controllers\api\home\LayoutController;
use App\Http\Controllers\api\home\OrderController;
use App\Http\Controllers\api\home\PaymentMethodController;
use App\Http\Controllers\api\home\ProductController as HomeProductController;
use App\Http\Controllers\api\home\ReturnController as HomeReturnController;
use App\Http\Controllers\api\home\ReviewController;
use App\Http\Controllers\api\home\TicketController as HomeTicketController;
use App\Http\Controllers\api\home\WishlistController;
use App\Http\Controllers\api\InstallerController;
use App\Http\Controllers\api\payment\PaymentCallbackController;
use App\Http\Controllers\api\payment\PaymentController;
use App\Http\Controllers\api\webhook\PaymentWebhookController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ApiKeyMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('installer')->group(function () {
    Route::get('/status', [InstallerController::class, 'status']);
    Route::get('/requirements', [InstallerController::class, 'requirements']);
    Route::post('/finish', [InstallerController::class, 'finish']);
    Route::post('/process', [InstallerController::class, 'process']);
})->withoutMiddleware(ApiKeyMiddleware::class);

Route::prefix('v1')->group(function () {

    Route::prefix('users')->group(function () {
        Route::post('send-otp', [RegisterController::class, 'sendOtp'])
            ->middleware(['throttle:6,1']);
        Route::post('verify-otp', [RegisterController::class, 'verifyOtp'])
            ->middleware(['throttle:6,1']);
        Route::post('register', [RegisterController::class, 'register'])
            ->middleware(['throttle:6,1']);
        Route::post('login', [AuthController::class, 'login'])
            ->middleware(['throttle:7,1']);

        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
            ->middleware(['throttle:5,1', 'guest']);
        Route::post('reset-password', [AuthController::class, 'resetPassword'])
            ->middleware(['throttle:5,1', 'guest']);

        Route::get('google-login', [GoogleAuthController::class, 'googleLogin'])->withoutMiddleware(ApiKeyMiddleware::class);
        Route::get('google-callback', [GoogleAuthController::class, 'googleCallback'])->withoutMiddleware(ApiKeyMiddleware::class);

        Route::middleware(['auth:sanctum', 'throttle:15,1'])->group(function () {
            Route::get('wallet', [WalletController::class, 'wallet']);
            Route::get('profile', [ProfileController::class, 'profile']);
            Route::post('update-profile', [ProfileController::class, 'updateProfile']);
            Route::post('password', [ProfileController::class, 'updatePassword']);
            Route::post('logout', [AuthController::class, 'logout']);
            Route::delete('delete-account', [ProfileController::class, 'deleteAccount']);
        });
    });

    Route::prefix('cart')->middleware('auth:sanctum')->group(
        function () {
            Route::post('/addToCart/{id}', [CartController::class, 'addToCart']);
            Route::get('/get', [CartController::class, 'cart']);
            Route::post('/coupon', [CartController::class, 'applyCoupon']);
            Route::delete('/coupon', [CartController::class, 'removeCoupon']);
            Route::put('/items/{id}', [CartController::class, 'updateQuantity'])->whereNumber('id');
            Route::delete('/delete/{id}', [CartController::class, 'deleteFromCart']);
            Route::delete('/clearCart', [CartController::class, 'clearCart']);
        }
    );

    Route::withoutMiddleware(ApiKeyMiddleware::class)->group(function () {
        Route::prefix('products')->group(function () {
            Route::get('/', [HomeProductController::class, 'index']);
            Route::get('/latest-four', [HomeProductController::class, 'latestFour']);
            Route::get('/random-three', [HomeProductController::class, 'randomThree']);
            Route::get('/random-four', [HomeProductController::class, 'randomFour']);
            Route::get('/featured', [HomeProductController::class, 'featured']);
            Route::get('/{product}', [HomeProductController::class, 'show']);
            Route::get('/{product}/related', [HomeProductController::class, 'related']);
        });

        Route::get('/categories', [HomeCategoryController::class, 'index']);
        Route::get('/categories/{slug}', [HomeCategoryController::class, 'show']);
        Route::get('/brands', [HomeBrandController::class, 'index']);
        Route::get('/home-content', [HomeContentController::class, 'index']);
        Route::get('/layout', [LayoutController::class, 'index']);
        Route::get('/payment-methods', [PaymentMethodController::class, 'index']);
        Route::post('/contact-us', [HomeContactUsController::class, 'store'])
            ->middleware('throttle:5,1');
        Route::get('/blog', [HomeBlogController::class, 'index']);
        Route::get('/blog/categories', [HomeBlogController::class, 'categories']);
        Route::get('/blog/tags', [HomeBlogController::class, 'tags']);
        Route::get('/blog/{slug}', [HomeBlogController::class, 'show']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/pay', [PaymentController::class, 'pay']);
        Route::get('/order/status/{id}', [PaymentController::class, 'orderStatus']);
        Route::prefix('addresses')->group(function () {
            Route::get('/', [AddressController::class, 'index']);
            Route::post('/', [AddressController::class, 'store']);
            Route::get('/{id}', [AddressController::class, 'show'])->whereNumber('id');
            Route::put('/{id}', [AddressController::class, 'update'])->whereNumber('id');
            Route::patch('/{id}', [AddressController::class, 'update'])->whereNumber('id');
            Route::delete('/{id}', [AddressController::class, 'destroy'])->whereNumber('id');
            Route::post('/{id}/default-shipping', [AddressController::class, 'defaultShipping'])->whereNumber('id');
            Route::post('/{id}/default-billing', [AddressController::class, 'defaultBilling'])->whereNumber('id');
        });
        Route::prefix('checkout')->group(function () {
            Route::get('/summary', [CheckoutController::class, 'summary']);
            Route::post('/select-address', [CheckoutController::class, 'selectAddress']);
            Route::post('/shipping-rates', [CheckoutController::class, 'shippingRates']);
            Route::post('/select-shipping', [CheckoutController::class, 'selectShipping']);
            Route::post('/place-order', [CheckoutController::class, 'placeOrder']);
        });
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{id}', [OrderController::class, 'show'])->whereNumber('id');
        Route::get('/orders/{order}/invoice/download', [HomeInvoiceController::class, 'download'])->whereNumber('order');
        Route::prefix('returns')->group(function () {
            Route::get('/', [HomeReturnController::class, 'index']);
            Route::get('/{id}', [HomeReturnController::class, 'show'])->whereNumber('id');
            Route::post('/{id}/cancel', [HomeReturnController::class, 'cancel'])->whereNumber('id');
        });
        Route::post('/orders/{order}/returns', [HomeReturnController::class, 'store'])->whereNumber('order');
        Route::prefix('wishlist')->group(function () {
            Route::get('/', [WishlistController::class, 'index']);
            Route::post('/{productId}', [WishlistController::class, 'toggle'])->whereNumber('productId');
            Route::delete('/{productId}', [WishlistController::class, 'destroy'])->whereNumber('productId');
        });
        Route::post('/products/{productId}/reviews', [ReviewController::class, 'store'])->whereNumber('productId');
        Route::prefix('support/tickets')->group(function () {
            Route::get('/', [HomeTicketController::class, 'index']);
            Route::post('/', [HomeTicketController::class, 'store']);
            Route::get('/{id}', [HomeTicketController::class, 'show'])->whereNumber('id');
            Route::post('/{id}/reply', [HomeTicketController::class, 'reply'])->whereNumber('id');
            Route::patch('/{id}/status', [HomeTicketController::class, 'updateStatus'])->whereNumber('id');
        });
    });

    Route::prefix('palletes')->group(function () {
        Route::get('/', [HomePalleteController::class, 'index']);
        Route::post('/', [HomePalleteController::class, 'create']);
        Route::get('/{pallete}', [HomePalleteController::class, 'show']);
        Route::post('/{pallete}', [HomePalleteController::class, 'update']);
    });

    Route::match(['get', 'post'], '/payment/paymob/callback', [PaymentCallbackController::class, 'paymob'])
        ->name('payment.paymob.callback')
        ->withoutMiddleware(ApiKeyMiddleware::class);
    Route::post('/webhooks/paymob', [PaymentWebhookController::class, 'paymob'])
        ->name('webhook.paymob')
        ->withoutMiddleware(ApiKeyMiddleware::class);
});

Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'login'])->withoutMiddleware(ApiKeyMiddleware::class);

    Route::prefix('orders')->middleware(['auth:sanctum', AdminMiddleware::class])->group(function () {
        Route::get('/', [AdminOrderController::class, 'index'])->middleware('permission:orders.view');
        Route::get('/{id}', [AdminOrderController::class, 'show'])->middleware('permission:orders.view')->whereNumber('id');
        Route::put('/{id}/status', [AdminOrderController::class, 'updateStatus'])->middleware('permission:orders.manage')->whereNumber('id');
        Route::patch('/{id}/order-status', [AdminOrderController::class, 'updateOrderStatus'])->middleware('permission:orders.manage')->whereNumber('id');
        Route::patch('/{id}/payment-status', [AdminOrderController::class, 'updatePaymentStatus'])->middleware('permission:orders.manage')->whereNumber('id');
        Route::patch('/{id}/shipping-status', [AdminOrderController::class, 'updateShippingStatus'])->middleware('permission:orders.manage')->whereNumber('id');
        Route::get('/{order}/invoice/download', [InvoiceController::class, 'download'])->middleware('permission:invoices.manage')->whereNumber('order');
        Route::post('/{order}/shipment/create', [ShipmentController::class, 'create'])->middleware('permission:shipments.manage')->whereNumber('order');
        Route::post('/{order}/shipment/buy-label', [ShipmentController::class, 'buyLabel'])->middleware('permission:shipments.manage')->whereNumber('order');
        Route::get('/{order}/shipment/track', [ShipmentController::class, 'track'])->middleware('permission:shipments.manage')->whereNumber('order');
        Route::patch('/{order}/shipment/status', [ShipmentController::class, 'updateStatus'])->middleware('permission:shipments.manage')->whereNumber('order');
        Route::delete('/{id}', [AdminOrderController::class, 'destroy'])->middleware('permission:orders.delete')->whereNumber('id');
    });

    Route::get('/dashboard/statistics', [DashboardController::class, 'statistics'])
        ->middleware(['auth:sanctum', AdminMiddleware::class, 'throttle:30,1', 'permission:dashboard.view|reports.view']);

    Route::prefix('shipping')->middleware(['auth:sanctum', AdminMiddleware::class, 'throttle:30,1', 'permission:shipping.manage'])->group(function () {
        Route::get('/methods', [ShippingMethodController::class, 'index']);
        Route::post('/methods', [ShippingMethodController::class, 'store']);
        Route::get('/methods/{id}', [ShippingMethodController::class, 'show'])->whereNumber('id');
        Route::put('/methods/{id}', [ShippingMethodController::class, 'update'])->whereNumber('id');
        Route::patch('/methods/{id}', [ShippingMethodController::class, 'update'])->whereNumber('id');
        Route::delete('/methods/{id}', [ShippingMethodController::class, 'destroy'])->whereNumber('id');
        Route::get('/zones', [ShippingZoneController::class, 'index']);
        Route::post('/zones', [ShippingZoneController::class, 'store']);
        Route::put('/zones/{id}', [ShippingZoneController::class, 'update'])->whereNumber('id');
        Route::patch('/zones/{id}', [ShippingZoneController::class, 'update'])->whereNumber('id');
        Route::delete('/zones/{id}', [ShippingZoneController::class, 'destroy'])->whereNumber('id');
        Route::get('/rates', [ShippingRateController::class, 'index']);
        Route::post('/rates', [ShippingRateController::class, 'store']);
        Route::put('/rates/{id}', [ShippingRateController::class, 'update'])->whereNumber('id');
        Route::patch('/rates/{id}', [ShippingRateController::class, 'update'])->whereNumber('id');
        Route::delete('/rates/{id}', [ShippingRateController::class, 'destroy'])->whereNumber('id');
    });

    Route::prefix('tax-rules')->middleware(['auth:sanctum', AdminMiddleware::class, 'throttle:30,1'])->group(function () {
        Route::get('/', [TaxRuleController::class, 'index']);
        Route::post('/', [TaxRuleController::class, 'store']);
        Route::get('/{id}', [TaxRuleController::class, 'show'])->whereNumber('id');
        Route::put('/{id}', [TaxRuleController::class, 'update'])->whereNumber('id');
        Route::patch('/{id}', [TaxRuleController::class, 'update'])->whereNumber('id');
        Route::delete('/{id}', [TaxRuleController::class, 'destroy'])->whereNumber('id');
    });

    Route::prefix('returns')->middleware(['auth:sanctum', AdminMiddleware::class, 'throttle:30,1', 'permission:returns.manage'])->group(function () {
        Route::get('/', [AdminReturnController::class, 'index']);
        Route::get('/{id}', [AdminReturnController::class, 'show'])->whereNumber('id');
        Route::post('/{id}/approve', [AdminReturnController::class, 'approve'])->whereNumber('id');
        Route::post('/{id}/reject', [AdminReturnController::class, 'reject'])->whereNumber('id');
        Route::post('/{id}/mark-received', [AdminReturnController::class, 'markReceived'])->whereNumber('id');
        Route::post('/{id}/refund', [AdminReturnController::class, 'refund'])->whereNumber('id');
    });

    Route::prefix('analytics')->middleware(['auth:sanctum', AdminMiddleware::class, 'throttle:30,1', 'permission:reports.view'])->group(function () {
        Route::get('/revenue', [AnalyticsController::class, 'revenue']);
        Route::get('/sales', [AnalyticsController::class, 'sales']);
        Route::get('/top-products', [AnalyticsController::class, 'topProducts']);
        Route::get('/top-categories', [AnalyticsController::class, 'topCategories']);
        Route::get('/top-customers', [AnalyticsController::class, 'topCustomers']);
    });

    Route::middleware(['auth:sanctum', AdminMiddleware::class, 'throttle:30,1'])->group(function () {
        Route::get('/export/products', [ImportExportController::class, 'exportProducts'])->middleware('permission:exports.manage');
        Route::get('/export/categories', [ImportExportController::class, 'exportCategories'])->middleware('permission:exports.manage');
        Route::get('/export/orders', [ImportExportController::class, 'exportOrders'])->middleware('permission:exports.manage');
        Route::post('/import/products', [ImportExportController::class, 'importProducts'])->middleware('permission:imports.manage');
        Route::post('/import/categories', [ImportExportController::class, 'importCategories'])->middleware('permission:imports.manage');
        Route::get('/import/sample/{type}', [ImportExportController::class, 'sample'])->middleware('permission:imports.manage');
    });

    Route::prefix('tickets')->middleware(['auth:sanctum', AdminMiddleware::class, 'throttle:30,1', 'permission:tickets.view'])->group(function () {
        Route::get('/', [TicketController::class, 'index']);
        Route::get('/{id}', [TicketController::class, 'show'])->whereNumber('id');
        Route::post('/{id}/reply', [TicketController::class, 'reply'])
            ->middleware('permission:tickets.reply')->whereNumber('id');
        Route::patch('/{id}', [TicketController::class, 'update'])
            ->middleware('permission:tickets.manage')->whereNumber('id');
    });

    Route::prefix('blog')->middleware(['auth:sanctum', AdminMiddleware::class, 'throttle:30,1', 'permission:blog.view'])->group(function () {
        Route::get('/posts', [BlogController::class, 'posts']);
        Route::post('/posts', [BlogController::class, 'storePost'])->middleware('permission:blog.manage');
        Route::get('/posts/{id}', [BlogController::class, 'showPost'])->whereNumber('id');
        Route::post('/posts/{id}', [BlogController::class, 'updatePost'])->middleware('permission:blog.manage')->whereNumber('id');
        Route::delete('/posts/{id}', [BlogController::class, 'destroyPost'])->middleware('permission:blog.manage')->whereNumber('id');
        Route::get('/categories', [BlogController::class, 'categories']);
        Route::post('/categories', [BlogController::class, 'storeCategory'])->middleware('permission:blog.manage');
        Route::put('/categories/{id}', [BlogController::class, 'updateCategory'])->middleware('permission:blog.manage')->whereNumber('id');
        Route::delete('/categories/{id}', [BlogController::class, 'destroyCategory'])->middleware('permission:blog.manage')->whereNumber('id');
        Route::get('/tags', [BlogController::class, 'tags']);
        Route::post('/tags', [BlogController::class, 'storeTag'])->middleware('permission:blog.manage');
        Route::put('/tags/{id}', [BlogController::class, 'updateTag'])->middleware('permission:blog.manage')->whereNumber('id');
        Route::delete('/tags/{id}', [BlogController::class, 'destroyTag'])->middleware('permission:blog.manage')->whereNumber('id');
    });

    Route::prefix('email-templates')->middleware(['auth:sanctum', AdminMiddleware::class, 'throttle:30,1', 'permission:email_templates.manage'])->group(function () {
        Route::get('/', [EmailTemplateController::class, 'index']);
        Route::post('/', [EmailTemplateController::class, 'store']);
        Route::get('/{id}', [EmailTemplateController::class, 'show'])->whereNumber('id');
        Route::put('/{id}', [EmailTemplateController::class, 'update'])->whereNumber('id');
        Route::delete('/{id}', [EmailTemplateController::class, 'destroy'])->whereNumber('id');
        Route::post('/{id}/preview', [EmailTemplateController::class, 'preview'])->whereNumber('id');
        Route::post('/{id}/test-send', [EmailTemplateController::class, 'testSend'])->whereNumber('id');
    });

    Route::prefix('permissions')->middleware(['auth:sanctum', AdminMiddleware::class, 'throttle:30,1', 'permission:permissions.manage'])->group(function () {
        Route::get('/', [PermissionController::class, 'index']);
        Route::put('/roles/{role}', [PermissionController::class, 'updateRole']);
    });

    Route::prefix('inventory')->middleware(['auth:sanctum', AdminMiddleware::class, 'throttle:30,1', 'permission:inventory.manage'])->group(function () {
        Route::get('/low-stock', [InventoryController::class, 'lowStock']);
        Route::get('/out-of-stock', [InventoryController::class, 'outOfStock']);
    });
    Route::patch('/products/{product}/stock', [InventoryController::class, 'updateProduct'])
        ->middleware(['auth:sanctum', AdminMiddleware::class, 'throttle:30,1', 'permission:inventory.manage'])
        ->whereNumber('product');
    Route::patch('/product-variants/{variant}/stock', [InventoryController::class, 'updateVariant'])
        ->middleware(['auth:sanctum', AdminMiddleware::class, 'throttle:30,1', 'permission:inventory.manage'])
        ->whereNumber('variant');

    Route::prefix('coupons')->middleware(['auth:sanctum', AdminMiddleware::class, 'throttle:30,1', 'permission:coupons.manage'])->group(function () {
        Route::get('/', [CouponController::class, 'index']);
        Route::post('/create', [CouponController::class, 'store']);
        Route::get('/{id}', [CouponController::class, 'show'])->whereNumber('id');
        Route::put('/{id}', [CouponController::class, 'update'])->whereNumber('id');
        Route::delete('/{id}', [CouponController::class, 'destroy'])->whereNumber('id');
    });

    Route::prefix('users')->middleware(['auth:sanctum', AdminMiddleware::class, 'throttle:30,1'])->group(function () {
        Route::get('/', [UserController::class, 'index'])->middleware('permission:customers.view');
        Route::get('/all/get', [UserController::class, 'all'])->middleware('permission:customers.view');
        Route::get('/user/count', [UserController::class, 'count'])->middleware('permission:customers.view');
        Route::get('/{id}', [UserController::class, 'show'])->middleware('permission:customers.view');
        Route::post('/create', [UserController::class, 'create'])->middleware('permission:users.manage');
        Route::post('/{id}', [UserController::class, 'update'])->middleware('permission:users.manage');
        Route::delete('/{id}', [UserController::class, 'destroy'])->middleware('permission:users.manage');
    });

    Route::prefix('brands')->middleware(['auth:sanctum', AdminMiddleware::class, 'throttle:30,1'])->group(function () {
        Route::get('/', [BrandController::class, 'index'])->middleware('permission:brands.view');
        Route::get('/all/brands', [BrandController::class, 'all'])->middleware('permission:brands.view');
        Route::get('/brand/count', [BrandController::class, 'count'])->middleware('permission:brands.view');
        Route::get('/trashed', [BrandController::class, 'trashed'])->middleware('permission:brands.manage');
        Route::post('/{id}/restore', [BrandController::class, 'restore'])->middleware('permission:brands.manage');
        Route::get('/{id}', [BrandController::class, 'show'])->middleware('permission:brands.view');
        Route::get('/{id}/products', [BrandController::class, 'products'])->middleware('permission:brands.view');
        Route::post('/create', [BrandController::class, 'create'])->middleware('permission:brands.manage');
        Route::post('/{id}', [BrandController::class, 'update'])->middleware('permission:brands.manage');
        Route::delete('/{id}', [BrandController::class, 'destroy'])->middleware('permission:brands.manage');
    });

    Route::prefix('categories')->middleware(['auth:sanctum', AdminMiddleware::class, 'throttle:30,1'])->group(function () {
        Route::get('/', [CategoreyController::class, 'index'])->middleware('permission:categories.view');
        Route::get('/all/categories', [CategoreyController::class, 'all'])->middleware('permission:categories.view');
        Route::get('/category/count', [CategoreyController::class, 'count'])->middleware('permission:categories.view');
        Route::get('/trashed', [CategoreyController::class, 'trashed'])->middleware('permission:categories.manage');
        Route::post('/{id}/restore', [CategoreyController::class, 'restore'])->middleware('permission:categories.manage');
        Route::get('/{id}', [CategoreyController::class, 'show'])->middleware('permission:categories.view');
        Route::get('/{id}/products', [CategoreyController::class, 'products'])->middleware('permission:categories.view');
        Route::post('/create', [CategoreyController::class, 'create'])->middleware('permission:categories.manage');
        Route::post('/{id}', [CategoreyController::class, 'update'])->middleware('permission:categories.manage');
        Route::delete('/{id}', [CategoreyController::class, 'destroy'])->middleware('permission:categories.manage');
    });

    Route::prefix('products')->middleware(['auth:sanctum', AdminMiddleware::class, 'throttle:30,1'])->group(function () {
        Route::get('/', [ProductController::class, 'index'])->middleware('permission:products.view');
        Route::get('/products/count', [ProductController::class, 'count'])->middleware('permission:products.view');
        Route::get('/trashed', [ProductController::class, 'trashed'])->middleware('permission:products.delete');
        Route::post('/{id}/restore', [ProductController::class, 'restore'])->middleware('permission:products.delete');
        Route::get('/{id}', [ProductController::class, 'show'])->middleware('permission:products.view');
        Route::post('/create', [ProductController::class, 'create'])->middleware('permission:products.write');
        Route::post('/{id}', [ProductController::class, 'update'])->middleware('permission:products.write');
        Route::delete('/{id}', [ProductController::class, 'destroy'])->middleware('permission:products.delete');
    });

    // --- New Admin Modules ---

    Route::prefix('trust-items')->middleware(['auth:sanctum', AdminMiddleware::class, 'throttle:30,1', 'permission:site_content.manage'])->group(function () {
        Route::get('/', [TrustItemController::class, 'index']);
        Route::get('/{id}', [TrustItemController::class, 'show'])->whereNumber('id');
        Route::post('/create', [TrustItemController::class, 'store']);
        Route::post('/{id}', [TrustItemController::class, 'update'])->whereNumber('id');
        Route::delete('/{id}', [TrustItemController::class, 'destroy'])->whereNumber('id');
    });

    Route::prefix('features')->middleware(['auth:sanctum', AdminMiddleware::class, 'throttle:30,1', 'permission:site_content.manage'])->group(function () {
        Route::get('/', [FeatureController::class, 'index']);
        Route::get('/{id}', [FeatureController::class, 'show'])->whereNumber('id');
        Route::post('/create', [FeatureController::class, 'store']);
        Route::post('/{id}', [FeatureController::class, 'update'])->whereNumber('id');
        Route::delete('/{id}', [FeatureController::class, 'destroy'])->whereNumber('id');
    });

    Route::prefix('testimonials')->middleware(['auth:sanctum', AdminMiddleware::class, 'throttle:30,1', 'permission:site_content.manage'])->group(function () {
        Route::get('/', [TestimonialController::class, 'index']);
        Route::get('/{id}', [TestimonialController::class, 'show'])->whereNumber('id');
        Route::post('/create', [TestimonialController::class, 'store']);
        Route::post('/{id}', [TestimonialController::class, 'update'])->whereNumber('id');
        Route::delete('/{id}', [TestimonialController::class, 'destroy'])->whereNumber('id');
    });

    Route::prefix('deals')->middleware(['auth:sanctum', AdminMiddleware::class, 'throttle:30,1', 'permission:site_content.manage'])->group(function () {
        Route::get('/', [DealController::class, 'index']);
        Route::get('/{id}', [DealController::class, 'show'])->whereNumber('id');
        Route::post('/create', [DealController::class, 'store']);
        Route::post('/{id}', [DealController::class, 'update'])->whereNumber('id');
        Route::delete('/{id}', [DealController::class, 'destroy'])->whereNumber('id');
    });

    Route::prefix('banners')->middleware(['auth:sanctum', AdminMiddleware::class, 'throttle:30,1', 'permission:site_content.manage'])->group(function () {
        Route::get('/', [BannerController::class, 'index']);
        Route::get('/{id}', [BannerController::class, 'show'])->whereNumber('id');
        Route::post('/create', [BannerController::class, 'store']);
        Route::post('/{id}', [BannerController::class, 'update'])->whereNumber('id');
        Route::delete('/{id}', [BannerController::class, 'destroy'])->whereNumber('id');
    });

    Route::prefix('nav-links')->middleware(['auth:sanctum', AdminMiddleware::class, 'throttle:30,1', 'permission:site_content.manage'])->group(function () {
        Route::get('/', [NavLinkController::class, 'index']);
        Route::get('/{id}', [NavLinkController::class, 'show'])->whereNumber('id');
        Route::post('/create', [NavLinkController::class, 'store']);
        Route::post('/{id}', [NavLinkController::class, 'update'])->whereNumber('id');
        Route::delete('/{id}', [NavLinkController::class, 'destroy'])->whereNumber('id');
    });

    Route::prefix('site-settings')->middleware(['auth:sanctum', AdminMiddleware::class, 'throttle:30,1', 'permission:site_settings.manage'])->group(function () {
        Route::get('/', [SiteSettingController::class, 'index']);
        Route::post('/batch', [SiteSettingController::class, 'batchUpdate']);
        Route::get('/{key}', [SiteSettingController::class, 'show'])->where('key', '.*');
        Route::post('/create', [SiteSettingController::class, 'store']);
        Route::post('/{key}', [SiteSettingController::class, 'update'])->where('key', '.*');
        Route::delete('/{key}', [SiteSettingController::class, 'destroy'])->where('key', '.*');
    });

    Route::prefix('contact-messages')->middleware(['auth:sanctum', AdminMiddleware::class, 'throttle:30,1'])->group(function () {
        Route::get('/', [ContactUsController::class, 'index'])->middleware('permission:contact_messages.view');
        Route::get('/{id}', [ContactUsController::class, 'show'])->middleware('permission:contact_messages.view')->whereNumber('id');
        Route::post('/{id}/reply', [ContactUsController::class, 'reply'])->middleware('permission:contact_messages.reply')->whereNumber('id');
    });

    Route::prefix('settings/database')->middleware(['auth:sanctum', AdminMiddleware::class, 'throttle:30,1', 'permission:database_settings.manage'])->group(function () {
        Route::get('/', [DatabaseSettingController::class, 'show']);
        Route::post('/test', [DatabaseSettingController::class, 'test']);
        Route::put('/', [DatabaseSettingController::class, 'update']);
    });

    Route::prefix('settings/application')->middleware(['auth:sanctum', AdminMiddleware::class, 'throttle:30,1', 'permission:settings.manage'])->group(function () {
        Route::get('/', [ApplicationSettingController::class, 'show']);
        Route::put('/', [ApplicationSettingController::class, 'update']);
        Route::post('/test-mail', [ApplicationSettingController::class, 'sendTestMail']);
    });
});
