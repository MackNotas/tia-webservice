
```php
 =====================================================================================
 					 __  __            _    _   _       _            
 					|  \/  | __ _  ___| | _| \ | | ___ | |_ __ _ ___ 
 					| |\/| |/ _` |/ __| |/ /  \| |/ _ \| __/ _` / __|
 					| |  | | (_| | (__|   <| |\  | (_) | || (_| \__ \
 					|_|  |_|\__,_|\___|_|\_\_| \_|\___/ \__\__,_|___/

			 					  ____   ___  _ ____  
			 					 |___ \ / _ \/ | ___| 
			 					   __) | | | | |___ \ 
			 					  / __/| |_| | |___) |
			 					 |_____|\___/|_|____/ 
  
 =====================================================================================
```
# Descrição
O `tia-webservice` é o proxy responsável por obter as informações diretamente do tia do Mackenzie, e devolver a resposta em JSON para o cliente final.
É suportado as seguintes áreas do TIA:
- Notas
- Grade horária
- Faltas
- Atividade Complementar
- Calendario (TIA e Moodle)
- Desempenho Academico

O `tiaLogin_v2.php` está deprecado e não deve ser utilizado. É feito um redirect para o `tiaLogin_v3.php` por meio do `.htaccess`

Em um mundo ideal, isso deveria seguir os padrões RESTFul

## Exemplo de Request
Todas as requisições devem ser feitas para o `tiaLogin_v3.php`

```bash
curl -X POST \
      -H "Contenttype: application/json" \
      -H "Content-Type: application/json" \
      -H "User-Agent: iPhone" \
      -H "Cache-Control: no-cache" \
      -d '{ "userTia" : "31338526", "userPass" : "senha", "userUnidade" : "001", "tipo" : "1" }' \
      "http://tia-webservice.herokuapp.com/tiaLogin_v3.php"
```

Onde:
- **userTia**: Tia do usuário
- **userPass**: Senha do usuario
- **userUnidade**: Unidade do usuario (001 = Sao Paulo/Campinas), a mesma do TIA
- **Tipo**: Tipo da request 
  - 1 = Notas
  - 2 = Horario
  - 3 = Faltas
  - 4 = Login
  - 5 = Ativ. Compl.
  - 6 = Calendario
  - 7 = Desempenho

# Requerimentos:
- PHP 7.0
- cURL
- Composer
