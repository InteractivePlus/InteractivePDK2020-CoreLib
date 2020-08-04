# InteractivePDK2020-CoreLib
 Backend System Core Library for InteractivePlus's New Identity Provider / Verification System   
 此项目是形随意动新身份提供/验证系统的后端基础支持库   

---

## 参与此项目 | Contribute to this project
### 中文

1. 确保您已经安装 `PHP >= 7.1.0` 和 `Composer`
2. 首先您需要Clone本项目, 然后在项目根目录运行`composer init`命令
3. 本项目源码位于src/目录中, 测试文件位于test/目录中

### English

1. Make sure that you have `PHP >= 7.1.0` and `Composer` installed on your device.
2. Clone this project to your local device and run `composer init`
3. The source codes are in the `src/` directory and the test files are in the `test/` directory.

## 设计理念
此核心库区别于外部接口实现, 是为了将原本直接对数据库进行的操作进行抽象化转而变为面对对象的操作.   
核心库的设计尽力降低了实际使用数据库类型的限制, 略作更改即可用于MariaDB / MySQL以外的数据库, 因为每个对象都设计了`readFromDataArray()` 和 `saveToDataArray()` 成员函数.   
核心库考虑到后期用户数增长的情况, 所有数据库连接都需要作为构建参数传入, 确保以后升级到数据库连接池没有任何问题.   