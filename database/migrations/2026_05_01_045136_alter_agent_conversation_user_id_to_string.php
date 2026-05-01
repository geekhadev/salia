<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Laravel AI stores the authenticated user id in agent_conversations.user_id.
     * WhatsApp webhooks use Twilio addresses (e.g. whatsapp:+569…) which must be stored as strings.
     */
    public function up(): void
    {
        foreach (['agent_conversations', 'agent_conversation_messages'] as $table) {
            if (! Schema::hasTable($table) || $this->agentUserIdColumnIsAlreadyString($table)) {
                continue;
            }

            $this->alterUserIdColumnToString($table);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['agent_conversations', 'agent_conversation_messages'] as $table) {
            if (! Schema::hasTable($table) || ! $this->agentUserIdColumnIsAlreadyString($table)) {
                continue;
            }

            $this->alterUserIdColumnToBigInteger($table);
        }
    }

    protected function agentUserIdColumnIsAlreadyString(string $table): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'pgsql') {
            $row = $connection->selectOne(
                'select data_type from information_schema.columns where table_schema = current_schema() and table_name = ? and column_name = ?',
                [$table, 'user_id']
            );

            return $row && in_array($row->data_type, ['character varying', 'text'], true);
        }

        if ($driver === 'sqlite') {
            $quotedTable = $connection->getPdo()->quote($table);
            foreach ($connection->select("pragma table_info({$quotedTable})") as $column) {
                if ($column->name === 'user_id') {
                    $type = strtolower((string) $column->type);

                    return str_contains($type, 'char') || $type === 'text' || str_contains($type, 'varchar');
                }
            }

            return false;
        }

        if ($driver === 'mysql') {
            $row = $connection->selectOne(
                'select data_type from information_schema.columns where table_schema = database() and table_name = ? and column_name = ?',
                [$table, 'user_id']
            );

            return $row && in_array($row->data_type, ['varchar', 'char', 'text'], true);
        }

        return false;
    }

    protected function alterUserIdColumnToString(string $table): void
    {
        $grammar = Schema::getConnection()->getQueryGrammar();
        $wrapped = $grammar->wrapTable($table);
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("alter table {$wrapped} alter column user_id type varchar(255) using (case when user_id is null then null else user_id::text end)");

            return;
        }

        if ($driver === 'mysql') {
            DB::statement("alter table {$wrapped} modify user_id varchar(255) null");

            return;
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->string('user_id', 255)->nullable()->change();
        });
    }

    protected function alterUserIdColumnToBigInteger(string $table): void
    {
        $grammar = Schema::getConnection()->getQueryGrammar();
        $wrapped = $grammar->wrapTable($table);
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("alter table {$wrapped} alter column user_id type bigint using (case when user_id is null or user_id !~ '^[0-9]+$' then null else user_id::bigint end)");

            return;
        }

        if ($driver === 'mysql') {
            DB::statement("alter table {$wrapped} modify user_id bigint unsigned null");

            return;
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->unsignedBigInteger('user_id')->nullable()->change();
        });
    }
};
