<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications_notices', function (Blueprint $table) {
            $table->id();

            // Eindeutige Referenz für externe Nutzung (APIs, Logs)
            $table->uuid('uuid')->unique();

            // Klassifizierung / Typen (z.B. "toast", "system", "alert", "email")
            $table->string('notice_type')->default('toast');

            // Kurzer Titel / Name der Nachricht (z.B. "Erfolg", "Fehler", "Update")
            $table->string('title')->nullable();

            // Hauptinhalt
            $table->text('message')->nullable();

            // Optional: Längere Beschreibung oder Zusatztext
            $table->text('description')->nullable();

            // Flexible Eigenschaften (z.B. Buttons, Links, Optionen)
            $table->json('properties')->nullable();

            // Empfänger: Nutzer oder Team (beide optional, kann auch "global" sein)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();

            // Polymorphe Verknüpfung (z.B. mit einer Aktivität, einem Modell etc.)
            $table->morphs('noticable');  // statt 'activityable' → neutral

            // Metadaten (z.B. Quelle, Trigger, Priorität, Kontext)
            $table->json('metadata')->nullable();

            // Status-Flags (optional, falls man Notifications als gelesen markieren will)
            $table->timestamp('read_at')->nullable();
            $table->boolean('dismissed')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications_notices');
    }
};