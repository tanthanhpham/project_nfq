### About Project
This is the final project for Unlock program.
### Installation using docker
1. Install docker: </br>
  Follow steps in the [link](https://docs.docker.com/desktop/windows/install/)
2. Clone this project using git:
    ```
    $ git clone git@github.com:tanthanhpham/project_nfq.git
    ```
3. Build docker:
    ```
    $ cd project_nfq\
    $ docker-compose up -d --force-recreate --build --remove-orphans
    ```
4. Install library:
    ```
    $ composer install
    ```
5. Create database
    ```
    $ php bin/console doctrine:database:create
    ```
### Call API
1. Get all products
    ```url
    GET 127.0.0.1/api/products
    ```
2. Get one product
    ```url
    GET 127.0.0.1/api/product/{id}
    ```
3. Add product into database
    ```url
    POST 127.0.0.1/api/products
    ```
4. Update product
    ```url
    PUT 127.0.0.1/api/products/{id}
    ```
5. Delete product
    ```url
    DELETE 127.0.0.1/api/products/{id}
    ```
