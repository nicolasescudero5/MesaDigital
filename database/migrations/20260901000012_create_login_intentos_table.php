<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateLoginIntentosTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('login_intentos', ['id' => false, 'primary_key' => ['id'], 'signed' => false]);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
              ->addColumn('ip_address', 'string', ['limit' => 45])
              ->addColumn('email', 'string', ['limit' => 150])
              ->addColumn('intentado_el', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['ip_address', 'email', 'intentado_el'], ['name' => 'idx_login_ip_email'])
              ->create();
    }
}
