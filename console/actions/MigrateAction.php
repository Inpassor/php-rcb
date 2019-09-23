<?php

namespace rcb\console\actions;

use \rcb\console\models\Migration;

class MigrateAction extends \rcb\console\Action
{

    protected $_migrationsDir = null;

    /**
     * @throws \Exception
     */
    protected function _checkMigrationTable(): void
    {
        $connection = $this->app->db->connect();
        if (!$connection->tableExists('{{migration}}')) {
            $connection->createTable('{{migration}}', [
                'version' => 'string',
                'apply_time' => 'integer',
                'PRIMARY KEY (version)'
            ]);
        }
    }

    protected function _up(array $parameters): void
    {
        // TODO: migrate up by $parameters[0] (number of migrations to apply)
        $migrationsData = Migration::find()->order('apply_time')->asArray()->all();

        $migrationsApplied = [];
        if ($migrationsData) {
            foreach ($migrationsData as $migrationData) {
                $migrationsApplied[] = $migrationData['version'];
            }
        }

        $files = scandir($this->_migrationsDir);
        foreach ($files as $file) {
            if (
                is_dir($this->_migrationsDir . DIRECTORY_SEPARATOR . $file)
                || strrpos($file, '.php') === false
            ) {
                continue;
            }
            $mVersion = str_replace('.php', '', $file);
            if (in_array($mVersion, $migrationsApplied)) {
                continue;
            }
            require_once($this->_migrationsDir . DIRECTORY_SEPARATOR . $file);

            echo 'Applying migration "' . $mVersion . '"... ';

            /** @var \rcb\db\postgres\Migration $mInstance */
            $mInstance = new $mVersion();
            $mInstance->up();
            (new Migration([
                'version' => $mVersion,
                'apply_time' => time(),
            ]))->save();

            echo 'done.' . PHP_EOL;
        }
    }

    /**
     * @param $parameters
     * @throws \Exception
     */
    protected function _down(array $parameters): void
    {
        // TODO: migrate down by $parameters[0] (number of migrations to discard)
        $migrations = Migration::find()->order('apply_time DESC')->all();
        if ($migrations) {
            /** @var \rcb\console\models\Migration $migration */
            foreach ($migrations as $migration) {
                $mFile = $this->_migrationsDir . DIRECTORY_SEPARATOR . $migration->version . '.php';
                require_once($mFile);

                echo 'Discarding migration "' . $migration->version . '"... ';

                /** @var \rcb\db\postgres\Migration $mInstance */
                $mInstance = new $migration->version();
                $mInstance->down();
                $migration->delete();

                echo 'done.' . PHP_EOL;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->_migrationsDir = APP_PATH . DIRECTORY_SEPARATOR . 'migrations';
    }

    /**
     * @inheritdoc
     */
    public function run(array $parameters = []): void
    {
        $this->_checkMigrationTable();
        if (!$parameters) {
            $parameters = ['up'];
        }
        $action = array_shift($parameters);
        switch ($action) {
            case 'up':
                $this->_up($parameters);
                break;
            case 'down':
                $this->_down($parameters);
                break;
        }
    }

}
