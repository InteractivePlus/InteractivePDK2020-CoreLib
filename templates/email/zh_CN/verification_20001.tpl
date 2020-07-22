<!DOCTYPE html>
<html>
<body>
    <h1 align="center">{{ systemName }}</h1>
    <p align="left">亲爱的 {{ userDisplayName }}, </p>
    <p align="left">您申请了更改您密码的操作, 如果要继续操作, 请点击下面的链接: </p>
    <br />
    <p align="center"><font size="5em"><a href="{{ veriLink }}">{{ veriLink }}</a></font></p>
    <br />
    <p align="left">或者如果您当前所在的页面需要验证码来继续操作, 请输入验证码: {{ veriCode }}</p>
    <p align="left">如果您未经任何操作就收到了此邮件, 请忽略它.</p>
    <p align="right">此致, {{ systemName }} 运营团队</p>
</body>
</html>