<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220419151759 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment ADD order_id INT NOT NULL');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D8D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id)');
        $this->addSql('CREATE INDEX IDX_6D28840D8D9F6D38 ON payment (order_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category CHANGE name name VARCHAR(50) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE image image VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE coupon CHANGE name name VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE alias alias VARCHAR(50) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE gallery CHANGE path path VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE messenger_messages CHANGE body body LONGTEXT NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE headers headers LONGTEXT NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE queue_name queue_name VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE `order` CHANGE status status VARCHAR(10) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE recipient_name recipient_name VARCHAR(50) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE address_delivery address_delivery LONGTEXT NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE recipient_phone recipient_phone VARCHAR(11) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE recipient_email recipient_email VARCHAR(100) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE reason_cancel reason_cancel LONGTEXT DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE subject_cancel subject_cancel VARCHAR(100) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D8D9F6D38');
        $this->addSql('DROP INDEX IDX_6D28840D8D9F6D38 ON payment');
        $this->addSql('ALTER TABLE payment DROP order_id, CHANGE currency_code currency_code VARCHAR(40) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE status status VARCHAR(50) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE token token VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE product CHANGE name name VARCHAR(50) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE description description LONGTEXT DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE material material LONGTEXT DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE color color VARCHAR(50) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE images images LONGTEXT NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE size CHANGE name name VARCHAR(50) NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE user CHANGE email email VARCHAR(180) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE password password VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE phone phone VARCHAR(11) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE address address VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE image image VARCHAR(255) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE name name VARCHAR(50) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
