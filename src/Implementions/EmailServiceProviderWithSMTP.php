<?php
namespace InteractivePlus\PDK2020Core\Implementions;
use InteractivePlus\PDK2020Core\Interfaces\EmailServiceProvider;
use InteractivePlus\PDK2020Core\Settings\Setting;
use PHPMailer\PHPMailer\PHPMailer;

class EmailServiceProviderWithSMTP extends EmailServiceProvider{
    private $_smtpClient;
    private $_fromName = '';
    private $_fromEmail = '';
    public function __construct(
        string $host,
        string $username,
        string $password,
        int $port = 465,
        string $smtpSecure = 'tls'
    ){
        $this->_smtpClient = new PHPMailer();
        $this->_smtpClient->isSMTP();
        $this->_smtpClient->Host = $host;
        $this->_smtpClient->SMTPAuth = true;
        $this->_smtpClient->Username = $username;
        $this->_smtpClient->Password = $password;
        $this->_smtpClient->Port = $port;
        $this->_smtpClient->SMTPSecure = $smtpSecure;
        $this->_fromEmail = $username;
        $this->setFromEmail($username);
    }
    public function addToAccount(string $address, string $name = '') : void{
        $this->_smtpClient->addAddress($address,$name);
    }
    public function addCCAccount(string $address, string $name = '') : void{
        $this->_smtpClient->addCC($address,$name);
    }
    public function addBccAccount(string $address, string $name = '') : void{
        $this->_smtpClient->addBCC($address,$name);
    }
    public function clearToAccount() : void{
        $this->_smtpClient->clearAddresses();
    }
    public function clearCCAcount() : void{
        $this->_smtpClient->clearCCs();
    }
    public function clearBccAccount() : void{
        $this->_smtpClient->clearBCCs();
    }
    public function setSubject(string $subject = '') : void{
        $this->_smtpClient->Subject = $subject;
    }
    public function setBody(string $body = '') : void{
        $this->_smtpClient->Body = $body;
        $this->_smtpClient->isHTML(true);
    }
    public function addEmbeddedImageAsAttachment(string $string, string $cid, string $fileName = '', string $mimeType = '') : void{
        $this->_smtpClient->addStringEmbeddedImage($string,$cid,$fileName,PHPMailer::ENCODING_BASE64,$mimeType);
    }
    public function addAttachment(string $string, string $fileName, string $mimeType = '') : void{
        $this->_smtpClient->addStringAttachment($string,$fileName,PHPMailer::ENCODING_BASE64,$mimeType);
    }
    public function clearAttachments() : void{
        $this->_smtpClient->clearAttachments();
    }
    public function setFromName(string $fromName = '') : void{
        $this->_fromName = $fromName;
        $this->_smtpClient->setFrom($this->_fromEmail,$this->_fromName);
    }
    public function setFromEmail(string $fromEmail = '') : void{
        $this->_fromEmail = $fromEmail;
        $this->_smtpClient->setFrom($fromEmail,$this->_fromName);
    }
    public function send() : bool{
        return $this->_smtpClient->send();
    }
}