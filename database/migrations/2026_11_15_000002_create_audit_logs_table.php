<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * audit_logs - one row per thing somebody (or the system) did.
 *
 * ---------------------------------------------------------------------------
 * WHY THIS TABLE
 * ---------------------------------------------------------------------------
 * The shop had no single place that said "who changed what, and when":
 *
 *   - an order note was ONE mutable column (orders.admin_note). Whoever
 *     overwrote it destroyed the previous text, and nothing recorded who or
 *     when.
 *   - an inventory movement was written straight into inventory_movements with
 *     DB::table()->insert() at nine different call sites, so no event fired
 *     anywhere and the "who" column was usually empty.
 *
 * So the two histories lived apart, in two shapes, and neither could answer
 * "what happened to this order?" without reading the code.
 *
 * ---------------------------------------------------------------------------
 * THE SHAPE
 * ---------------------------------------------------------------------------
 *   action        what happened, in a stable dotted name (order.note.updated)
 *   subject_type  what it happened to (order, product, inventory, courier)
 *   subject_id    the id in that table
 *   before/after  the JSON of the values around the change, so a note edit
 *                 keeps the text it replaced
 *   actor_name    a snapshot of the user's name, so the log still reads
 *                 correctly after the user is deleted
 *   source        web | api | system | courier
 *
 * The table is append-only by convention: nothing in the app updates or
 * deletes a row.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Who did it. NULL means the system did it (a webhook, a sync).
            $table->unsignedBigInteger('user_id')->nullable()->index();

            // The name at the time, so the log survives a deleted user.
            $table->string('actor_name', 120)->nullable();

            // What happened: order.note.updated, inventory.movement.recorded, ...
            $table->string('action', 80)->index();

            // What it happened to.
            $table->string('subject_type', 40)->nullable()->index();
            $table->string('subject_id', 60)->nullable()->index();

            $table->text('description')->nullable();

            // The values around the change.
            $table->json('before')->nullable();
            $table->json('after')->nullable();

            $table->string('source', 20)->default('web');
            $table->string('ip', 45)->nullable();

            $table->timestamps();

            // The lookup the screen actually does: "everything about this order".
            $table->index(['subject_type', 'subject_id']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
