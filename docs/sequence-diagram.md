# Sequence Diagram - Manejo_Finanzas

## Flujo Principal: Request del usuario

```mermaid
sequenceDiagram
    participant User as Usuario
    participant Browser as Navegador
    participant Apache as Apache/Web
    participant Router as Router
    participant Controller as Controller
    participant Model as Model (ORM)
    participant DB as MySQL
    participant View as View

    User->>Browser: Accede a URL
    Browser->>Apache: HTTP Request
    Apache->>Router: index.php
    
    Note over Router: session_start()
    Router->>Router: getUrl() + getController()
    Router->>Controller: new MainController()
    
    Note over Controller: __construct()
    Controller->>Model: new Inflow()/Outflow()
    Model->>DB: SELECT * FROM inflows
    DB-->>Model: Resultados
    Model-->>Controller: datos
    
    Controller->>View: render()
    View-->>Browser: HTML Response
    Browser-->>User: Página renderizada
```

## Flujo: Login

```mermaid
sequenceDiagram
    participant User
    participant Router
    participant AuthController
    participant Authentication
    participant User as Model
    participant DB
    
    User->>Router: /auth/login
    Router->>AuthController: __construct()
    AuthController->>Authentication: login(post)
    Authentication->>User: findByEmail()
    User->>DB: SELECT WHERE email=?
    DB-->>User: user data
    Authentication->>Authentication: password_verify()
    Authentication-->>AuthController: ok/error
    AuthController->>Router: $_SESSION['id_user']
    Router-->>User: redirect /main
```

## Flujo: Registrar Egreso

```mermaid
sequenceDiagram
    participant User
    participant Router
    participant OutflowController
    participant Outflow as Model
    participant DB
    
    User->>Router: /outflow/create (POST)
    Router->>OutflowController: create(post)
    OutflowController->>Outflow: insert(data)
    Outflow->>DB: INSERT INTO outflows
    DB-->>Outflow: id_outflow
    Outflow-->>OutflowController: ok
    OutflowController-->>User: redirect /outflows
```

---

## Resumen de componentes

| Componente | Rol |
|------------|-----|
| `index.php` | Entry point |
| `Router` | Parsea URL → controller/method |
| `Controller` | Lógica de negocio |
| `Model (Orm)` | Abstrae consultas SQL |
| `View` | Renderiza HTML |
| `Helper` | Utilidades (dates, redirect, etc) |