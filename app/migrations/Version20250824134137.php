<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250824134137 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE todo_collab (to_do_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_A22C89845BE9ECD7 (to_do_id), INDEX IDX_A22C8984A76ED395 (user_id), PRIMARY KEY(to_do_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE todo_collab ADD CONSTRAINT FK_A22C89845BE9ECD7 FOREIGN KEY (to_do_id) REFERENCES to_do (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE todo_collab ADD CONSTRAINT FK_A22C8984A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE todo_collab DROP FOREIGN KEY FK_A22C89845BE9ECD7');
        $this->addSql('ALTER TABLE todo_collab DROP FOREIGN KEY FK_A22C8984A76ED395');
        $this->addSql('DROP TABLE todo_collab');
    }
}
