<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250629113848 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE todo_tags (to_do_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_306DF2395BE9ECD7 (to_do_id), INDEX IDX_306DF239BAD26311 (tag_id), PRIMARY KEY(to_do_id, tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE todo_tags ADD CONSTRAINT FK_306DF2395BE9ECD7 FOREIGN KEY (to_do_id) REFERENCES to_do (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE todo_tags ADD CONSTRAINT FK_306DF239BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE to_do ADD category_id INT DEFAULT NULL, ADD content VARCHAR(255) NOT NULL, DROP category, CHANGE is_done is_done TINYINT(1) NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE tag share_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE to_do ADD CONSTRAINT FK_1249EDA012469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE to_do ADD CONSTRAINT FK_1249EDA0A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1249EDA0D6594DD6 ON to_do (share_token)');
        $this->addSql('CREATE INDEX IDX_1249EDA012469DE2 ON to_do (category_id)');
        $this->addSql('CREATE INDEX IDX_1249EDA0A76ED395 ON to_do (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE todo_tags DROP FOREIGN KEY FK_306DF2395BE9ECD7');
        $this->addSql('ALTER TABLE todo_tags DROP FOREIGN KEY FK_306DF239BAD26311');
        $this->addSql('DROP TABLE todo_tags');
        $this->addSql('ALTER TABLE to_do DROP FOREIGN KEY FK_1249EDA012469DE2');
        $this->addSql('ALTER TABLE to_do DROP FOREIGN KEY FK_1249EDA0A76ED395');
        $this->addSql('DROP INDEX UNIQ_1249EDA0D6594DD6 ON to_do');
        $this->addSql('DROP INDEX IDX_1249EDA012469DE2 ON to_do');
        $this->addSql('DROP INDEX IDX_1249EDA0A76ED395 ON to_do');
        $this->addSql('ALTER TABLE to_do ADD category LONGTEXT DEFAULT NULL, DROP category_id, DROP content, CHANGE is_done is_done TINYINT(1) DEFAULT NULL, CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE share_token tag VARCHAR(255) DEFAULT NULL');
    }
}
