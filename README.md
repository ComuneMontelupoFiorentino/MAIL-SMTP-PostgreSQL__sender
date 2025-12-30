# SERVIZIO DI INVIO MAIL
Lo script consente l'invio di mail tramite SMTP da una tabella di coda, tramite semplice configurazione del file mail_params.ini (utile per test).

**Keywords**: MAIL, ALERT, SMTP, PostgreSQL, Riuso, PA

## Requisiti di funzionamento

- Server con PHP versione `8` o superiore
- PHP deve essere compilato con le seguenti estensioni: `php-cli`, `php-pgsql`
- database `Postgresql` versione 15 o superiore

## Installazione

Lo script non necessita di alcuna installazione specifica, è sufficiente copiare il contenuto completo della cartella principale in una cartella di destinazione sul server sul quale si vorranno lanciare le funzionalità.

## Funzionamento generale dello script

Per ogni funzionalità, è possibile lanciare lo script in due modalità distinte, una modalità di `test` ed una modalità di `produzione`.

**Elenco delle funzionalità**

- `SingleMail`: Consente di inviare una mail definendo i parametri nel file mail_params.ini (utile per test).
- `QueueMail`:  Consente di inviare mail per ogni record della tabella PostgreSQL prelevando i parametri (Recipient, Subject, body, cc, attachments..) dal DB e i parametri del servizio mail di invio da mail_service.ini. Tramite trigger sul DB può essere popolata la tabella e tramite job schedulati il servizio invia le mail e aggiorna lo stato nella tabella
- `Test`:       Consente di effettuare test di connessione al DBMS e dei parametri SMTP

### Struttura delle cartelle

```bash
|── cartella principale
    |── Classes/
        |── Services/
            |── MailService.php
            |── MailQueueService.php
            |── Logger.php
            |── PostgresConnection.php
        |── Tasks/
            |── SendMailTask.php
            |── Dto/
                |── MailMessage.php
    |── config/
        |── mail_service.ini
        |── mail_params.ini
        |── pg_service.conf
    |── logs/
    |── img/
        |── logo.png
    |── bootstrap.php
    |── send_mail.php
```
## Configurazione
Configurare il file `config/pg_service.conf` con i parametri di connessione al db.
Il file può contenere due configurazioni `pg_test` e `pg_prod`. 
Questa distinzione è stata introdotta per garantire una maggiore flessibilità e test. Nulla vieta di impostare la stessa configurazione sia per test che per produzione.

Per i dettagli sulla struttura e sulla configurazione del file pg_service.conf consultare il [manuale](https://www.postgresql.org/docs/current/libpq-pgservice.html) Postgres dedicato.

Configurare il file `config/mail_service.ini` con i parametri del servizio mail da utilizzare.
Anche in questo caso è presente la distinzione tra ambiente di test e produzione.

```ìni
; Servizio mail di test per gmail
[test]
host=
auth=true
secure=
port=
user=
password=
mail_from=no-reply@mail.it
name_from=
```

## Utilizzo della funzionalità

Una volta completati gli step di configurazione precedenti, è possibile lanciare la funzionalità da riga di comando posizionandosi nella cartella principale (stesso livello del file `send_mail.php`)

```cli
$ php sync.php [ambiente] [comando] [filtro]
```

Per qualsiasi funzionalità è necessario definire ambiente di esecuzione e comando

### Ambiente

La definizione dell'ambiente di esecuzione è obbligatorio, quindi definire uno tra:

- `--test`, lancio in ambiente di test
- `--prod`, lancio in ambiente di produzione

in caso nessuna o entrambe le opzioni vengano specificate, lo script terminerà con errore.

### Comando

I comandi possono necessitare di filtri

| **FUNZIONALITA**              | **COMANDO**      |
|-------------------------------|------------------|
| SingleMail                    | -p               |
| QueueMail                     | -q               |
| Test                          | -t               |

Esempi:

**SingleMail** 
```ìni
php sync.php -prod -p
```
**QueueMail**
```ìni
php sync.php -prod -q
```
**Test**
```ìni
php sync.php -test -t
```

### Logs

Lo script ad ogni esecuzione aggiornerà un di file log nel percorso `/Logs/[anno]/[mese]/` denominato `{comando}.log`.
I log per i test sono in modalità verbosa visualizzabile sia su termninale che su errors.log
Con i comando `-t` puoi ricevere i seguenti errori:
| **SERVIZIO**                  | **PARAMETRO**           | **ERORRE**                        |
|-------------------------------|-------------------------|-----------------------------------|
| SMTP                          |host, auth, secure, port | "Timeout o host non raggiungibile"|
| SMTP                          |user, password           | "Could not authenticate"          |
| DBMS                          |                         | SQL STATEMENT errror              |
