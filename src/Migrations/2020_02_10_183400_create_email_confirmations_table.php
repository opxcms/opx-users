<?php

use Illuminate\Support\Facades\Schema;
use Core\Foundation\Database\OpxBlueprint;
use Core\Foundation\Database\OpxMigration;

class CreateEmailConfirmationsTable extends OpxMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $this->schema->create('users_email_confirmations', static function (OpxBlueprint $table) {

            $table->parentId('user_id');

            $table->string('email');

            $table->string('token');

            $table->timestamp('created_at');

            $table->timestamp('expires_at');

            $table->index(['user_id', 'token']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('users_email_confirmations');
    }
}
