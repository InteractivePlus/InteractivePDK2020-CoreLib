<?php
namespace InteractivePlus\PDK2020Core\Interfaces;
abstract class EmailServiceProvider{
    public abstract function addToAccount(string $address, string $name = '') : void;
    public abstract function addCCAccount(string $address, string $name = '') : void;
    public abstract function addBccAccount(string $address, string $name = '') : void;
    public abstract function clearToAccount() : void;
    public abstract function clearCCAcount() : void;
    public abstract function clearBccAccount() : void;
    public abstract function setSubject(string $subject = '') : void;
    public abstract function setBody(string $body = '') : void;
    public abstract function addEmbeddedImageAsAttachment(string $string, string $cid, string $fileName = '', string $mimeType = '') : void;
    public abstract function addAttachment(string $string, string $fileName, string $mimeType = '') : void;
    public abstract function clearAttachments() : void;
    public abstract function setFromName(string $fromName = '') : void;
    public abstract function setFromEmail(string $fromEmail = '') : void;
    public abstract function setCharset(string $charset = 'UTF-8') : void;
    public function clear(){
        $this->clearToAccount();
        $this->clearCCAcount();
        $this->clearBccAccount();
        $this->setSubject();
        $this->setBody();
        $this->clearAttachments();
        $this->setFromEmail();
        $this->setFromName();
    }
    public abstract function send() : bool;
}