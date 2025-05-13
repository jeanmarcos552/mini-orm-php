# Mini ORM PHP – Solução sem Bibliotecas Externas

## 📌 Introdução
Este projeto surgiu da necessidade de melhorar a produtividade da equipe no desenvolvimento de aplicações PHP, sem utilizar bibliotecas externas devido a restrições contratuais. Antes, as inserções eram feitas diretamente no PHP, o que tornava o código propenso a bugs e difícil de manter. Como solução, desenvolvi este **Mini ORM**, permitindo uma abordagem mais estruturada e segura para interações com o banco de dados.

## ⚙️ Recursos
- Abstração para **SELECT**, **INSERT**, **UPDATE** e **DELETE**
- Uso de **PDO** para segurança contra SQL Injection
- Encadeamento de métodos para queries mais intuitivas
- Facilidade de integração com projetos existentes

## 🚀 Benefícios
- Redução de erros e aumento da produtividade da equipe
- Código mais legível e modular
- Compatível com projetos que não podem usar bibliotecas externas

## 🔧 Como Usar
Instancie a classe e utilize métodos encadeados:

```php
$db = new DB();
$users = $db->select(['id', 'name'])->from('users')->where(['active' => 1])->execute();
```

## 🤝 Contribuição
Sinta-se à vontade para sugerir melhorias e otimizações! Toda contribuição será bem-vinda.
