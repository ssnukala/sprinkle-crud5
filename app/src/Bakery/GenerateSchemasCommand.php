<?php

declare(strict_types=1);

/*
 * UserFrosting Core Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/sprinkle-core
 * @copyright Copyright (c) 2021 Alexander Weissman & Louis Charette
 * @license   https://github.com/userfrosting/sprinkle-core/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD5\Bakery;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use UserFrosting\Bakery\WithSymfonyStyle;
use UserFrosting\Sprinkle\Core\Bakery\Event\BakeCommandEvent;


/**
 * Bake command.
 * Umbrella command used to run multiple sub-commands at once.
 */
final class CRUD5Command extends Command
{
    use WithSymfonyStyle;


    protected $name = 'generate:schemas';
    protected $description = 'Generate YAML schema files for all tables in the database';


    /**
     * @var string The UserFrosting ASCII art.
     */
    public string $title = "
 _   _              ______             _   _
| | | |             |  ___|           | | (_)
| | | |___  ___ _ __| |_ _ __ ___  ___| |_ _ _ __   __ _
| | | / __|/ _ \ '__|  _| '__/ _ \/ __| __| | '_ \ / _` |
| |_| \__ \  __/ |  | | | | | (_) \__ \ |_| | | | | (_| |
 \___/|___/\___|_|  \_| |_|  \___/|___/\__|_|_| |_|\__, |
                                                    __/ |
                                                   |___/";

    /**
     * @param \UserFrosting\Event\EventDispatcher $eventDispatcher
     */
    public function __construct(
        protected EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $list = implode(', ', $this->aggregateCommands());

        $this->setName('crud5')
            ->setDescription('UserFrosting installation command')
            ->setHelp('This command combine the following commands : ' . $list);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->writeln("<info>{$this->title}</info>");
        // Get the database connection from UserFrosting
        $db = $this->ci->db;

        // Fetch all tables in the schema
        $tables = $db->raw("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);

        // Iterate through tables
        foreach ($tables as $table) {
            // Get columns information for each table
            $columns = $db->table($table)->get();

            // Create YAML schema file content
            $yamlContent = "table: $table\n";

            foreach ($columns as $column) {
                $yamlContent .= "  - column: $column->column_name\n";
                $yamlContent .= "    type: $column->data_type\n";
                // Add more details as needed
            }

            // Save YAML file
            $yamlFilePath = "path/to/schemas/$table.yaml";
            file_put_contents($yamlFilePath, $yamlContent);

            $this->info("Schema generated for table '$table'");
        }

        $this->info('Schemas generated successfully!');

        return self::SUCCESS;
    }
}
