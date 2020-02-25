<?php

use Illuminate\Support\Facades\Schema;
use Core\Foundation\Database\OpxBlueprint;
use Core\Foundation\Database\OpxMigration;

class CreateUsersTable extends OpxMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $this->schema->create('users', static function (OpxBlueprint $table) {

            $table->increments('id');

            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password');

            $table->boolean('is_email_confirmed')->default(false);
            $table->boolean('is_activated')->default(false);
            $table->boolean('is_blocked')->default(false);

            $table->timestamp('last_login')->nullable();
            $table->timestamp('last_activity')->nullable();

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::drop('users');
    }
}
