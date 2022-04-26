# e-money-kelompok-9
# PAYPHONE REST API

## Kelompok 09
1. Asima Prima Yohana - 5027201009
2. Brilianti Puspita S ari - 5027201070
3. Adinda Putri Audyna - 5027201073

## Register

### Method
`post`

### endpoint
`.../api/register`

### auth
tidak ada 

### parameter 
{username, password, telpon}

### output postman
![](dokum01/img1.jpg)




## Login

### Method
`post`

### endpoint
`.../api/login`

### auth
tidak ada 

### parameter 
{username, telpon}

### output postman
![](dokum01/login.jpg)



## Top Up

### Method
`post`

### endpoint
`.../api/top-up`

### auth
token
![](dokum01/token.jpg)

### parameter 
tidak ada

### output postman
![](dokum01/topup.jpg)


## Cek Saldo

### Method
`Get`

### endpoint
`.../api/cek-saldo`

### auth
token

### parameter 
tidak ada

### output postman
![](dokum01/ceksaldo.jpg)



## Transfer

### Method
`post`

### endpoint
`.../api/transfer`

### auth
token

### parameter 
{telepon, jumlah}

### output postman
![](dokum01/transfer.jpg)


## history

### Method
`get`

### endpoint
`.../api/history`

### auth
token

### parameter 
tidak ada

### output postman
![](dokum01/history.jpg)

## Invoice

### Method
`get`

### endpoint
`.../api/invoice/5`

### auth
token

### parameter 
tidak ada

### output postman
![](dokum01/invoice.jpg)
