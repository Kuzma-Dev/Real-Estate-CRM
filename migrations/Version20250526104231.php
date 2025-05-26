<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250526104231 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE agent ADD phone VARCHAR(255) DEFAULT NULL, ADD email VARCHAR(255) DEFAULT NULL, ADD photo VARCHAR(255) DEFAULT NULL, ADD bio LONGTEXT DEFAULT NULL, ADD status VARCHAR(50) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE demand ADD agent_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE demand ADD CONSTRAINT FK_428D79733414710B FOREIGN KEY (agent_id) REFERENCES agent (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_428D79733414710B ON demand (agent_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE demand DROP FOREIGN KEY FK_428D79733414710B
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_428D79733414710B ON demand
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE demand DROP agent_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE agent DROP phone, DROP email, DROP photo, DROP bio, DROP status
        SQL);
    }
}
