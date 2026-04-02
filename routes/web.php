<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CashSessionController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\RepairController;
use App\Http\Controllers\MaintenanceController;

Route::get('/login', [AuthController::class,'showLogin'])->name('login');
Route::post('/login', [AuthController::class,'login'])->name('login.post');
Route::post('/logout', [AuthController::class,'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/', function() {
        $user = auth()->user();
        if ($user && $user->role === 'technician') {
            return redirect()->route('repairs.index');
        }
        return redirect()->route('pos.index');
    });
        Route::middleware(['role:seller,admin,technician'])->group(function () {
        Route::get('/pos', [SaleController::class,'index'])->name('pos.index');
        Route::get('/pos/sales', [SaleController::class,'sales'])->name('pos.sales');
        Route::post('/pos/sales/{sale}/request-delete', [SaleController::class,'requestDelete'])->name('pos.sales.request-delete');
        Route::post('/pos/sales/{sale}/cancel-delete', [SaleController::class,'cancelDeleteRequest'])->name('pos.sales.cancel-delete');
        Route::delete('/pos/sales/{sale}', [SaleController::class,'destroySale'])->name('pos.sales.destroy');
        Route::post('/pos/add-item', [SaleController::class,'addItem'])->name('pos.addItem');
    Route::post('/pos/add-recharge', [SaleController::class,'addRecharge'])->name('pos.addRecharge');
        Route::post('/pos/remove-item', [SaleController::class,'removeItem'])->name('pos.removeItem');
        Route::post('/pos/checkout', [SaleController::class,'checkout'])->name('pos.checkout');
        Route::get('/pos/session/summary', [SaleController::class,'sessionSummary'])->name('pos.summary');
        Route::get('/pos/search', [SaleController::class,'search'])->name('pos.search');
        Route::get('/cash/close-summary', [CashSessionController::class,'closeSummary'])->name('cash.close.summary');
        Route::post('/cash/close', [CashSessionController::class,'close'])->name('cash.close');
    Route::post('/cash/movements', [CashSessionController::class,'addMovement'])->name('cash.movement.add');
    Route::delete('/cash/movements/{movement}', [CashSessionController::class,'destroyMovement'])->name('cash.movement.delete');
    Route::post('/cash/deposit/add', [CashSessionController::class,'addDeposit'])->name('cash.deposit.add');
    Route::get('/reports/session.csv', [\App\Http\Controllers\ReportController::class,'exportSessionCsv'])->name('reports.session.csv');
    });
    Route::middleware(['role:seller,admin,technician'])->group(function () {
        // Reparaciones (todos pueden recibir, técnico completa)
        Route::get('/repairs', [RepairController::class,'index'])->name('repairs.index');
        Route::get('/repairs/history', [RepairController::class,'history'])->name('repairs.history');
        Route::post('/repairs', [RepairController::class,'store'])->name('repairs.store');
        Route::patch('/repairs/{repair}', [RepairController::class,'update'])->name('repairs.update');
        Route::delete('/repairs/{repair}', [RepairController::class,'destroy'])->name('repairs.destroy');
        Route::post('/repairs/{repair}/warranty', [RepairController::class,'markAsWarranty'])->name('repairs.warranty');
    });
    // Pedidos accesibles para vendedores y admin
    Route::middleware(['role:seller,admin'])->group(function () {
        Route::get('/orders', [OrderController::class,'index'])->name('orders.index');
        Route::post('/orders', [OrderController::class,'store'])->name('orders.store');
        Route::patch('/orders/{order}', [OrderController::class,'update'])->name('orders.update');
        Route::delete('/orders/{order}', [OrderController::class,'destroy'])->name('orders.destroy');
        Route::post('/orders/{order}/import-products', [OrderController::class,'importProducts'])->name('orders.import');
    });

    Route::middleware(['role:admin'])->group(function () {
        // Usuarios (StoreCell)
        Route::get('/users', [AdminUserController::class,'index'])->name('admin.users.index');
        Route::post('/users', [AdminUserController::class,'store'])->name('admin.users.store');
    Route::patch('/users/{user}', [AdminUserController::class,'update'])->name('admin.users.update');
    Route::delete('/users/{user}', [AdminUserController::class,'destroy'])->name('admin.users.destroy');
    Route::post('/users/{user}/toggle-block', [AdminUserController::class,'toggleBlock'])->name('admin.users.toggle');
        Route::resource('products', ProductController::class);
        Route::resource('categories', CategoryController::class)->only(['index','store','update','destroy']);
        Route::get('/cash/open', [CashSessionController::class,'create'])->name('cash.open');
        Route::post('/cash/open', [CashSessionController::class,'store'])->name('cash.store');
        Route::get('/dashboard', [DashboardController::class,'index'])->name('dashboard');
        // Configuración
        Route::get('/settings', [\App\Http\Controllers\SettingsController::class,'index'])->name('settings.index');
        Route::post('/settings', [\App\Http\Controllers\SettingsController::class,'update'])->name('settings.update');
        // Mantenimiento
        Route::post('/admin/reset-keep-users', [MaintenanceController::class,'resetKeepUsers'])->name('admin.reset.keep-users');
        Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'index'])->name('profile.index');
        Route::patch('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    
        });
});
