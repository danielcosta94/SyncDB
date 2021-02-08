# Sync DB

Sync DB is a script made to copy the pretended source data from **Moodle DB** to **HT DB** and stores all data from evaluations and tests of the students into the respective tables

# Important notes to deployment

- Create a new job in crontab are located and set a time when it allows to run the script until the end without overlapping the previously launched process

### Installation and configuration

- Install `PHP` (if not installed)
- Copy and deploy this microservice in the desired location
- Copy and rename template file configuration`config.php.example` to `config.php` and set the all the needed configuration variables
- Go to `php.ini` search configuration `max_execution_time` and set this variable to value that **allows to run the script until the end** or set to `0` to be **unlimited**
- Add a new entry to crontab list along with the adjusted schedule time execution

**Example to run every 30 minutes**
  ```sh
*/30 * * * * php <path_to_project>/sync_db.php
```