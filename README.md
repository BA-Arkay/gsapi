# Store-API

# Composer command
1. php artisan migrate:fresh
2. php artisan db:seed. (seed for default store)

# After Pull
1. php artisan migrate

# Database .env
1. change DB_DATABASE
2. change DB_USERNAME
3. change DB_PASSWORD
4. add FACTORY=nameOfTheFactory

# Items
1. Received_items : is_received = 1
2. In store but not racked : is_received = 1 && is_boxed =0 && is_delivered =0
3. Racked : is_received =1 && is_boxed = 1
4. Received but not racked but delivered: is_received =1 && is_boxed =0 && is_delivered = 1

# Routes
* Stores
    * 