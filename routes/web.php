<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatworkMemberController;
use App\Http\Controllers\ProjectAttachmentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectHistoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/chatwork', [ChatworkMemberController::class, 'index'])->name('chatwork.index');

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('app.auth')->group(function () {
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::post('/projects/sub', [ProjectController::class, 'storeSubProject'])->name('projects.store-sub');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::post('/projects/{project}/status', [ProjectController::class, 'updateStatus'])->name('projects.update-status');

    Route::get('/projects/{project}/attachments', [ProjectAttachmentController::class, 'index'])->name('projects.attachments.index');
    Route::post('/projects/{project}/attachments', [ProjectAttachmentController::class, 'store'])->name('projects.attachments.store');
    Route::get('/attachments/{attachment}/download', [ProjectAttachmentController::class, 'download'])->name('attachments.download');
    Route::delete('/attachments/{attachment}', [ProjectAttachmentController::class, 'destroy'])->name('attachments.destroy');

    Route::get('/histories/{history}', [ProjectHistoryController::class, 'show'])->name('histories.show');
    Route::post('/histories', [ProjectHistoryController::class, 'store'])->name('histories.store');
    Route::put('/histories/{history}', [ProjectHistoryController::class, 'update'])->name('histories.update');
    Route::delete('/histories/{history}', [ProjectHistoryController::class, 'destroy'])->name('histories.destroy');

    Route::get('/chatwork/members', [ChatworkMemberController::class, 'list'])->name('chatwork.members.list');
    Route::get('/chatwork/room-members', [ChatworkMemberController::class, 'roomMembers'])->name('chatwork.room-members');
    Route::post('/chatwork/members', [ChatworkMemberController::class, 'store'])->name('chatwork.members.store');
    Route::delete('/chatwork/members/{member}', [ChatworkMemberController::class, 'destroy'])->name('chatwork.members.destroy');
});
