# Component Diagram - Manejo_Finanzas

```mermaid
graph TD
    subgraph "Client Layer"
        Browser[Navegador]
        Mobile[App Móvil]
    end

    subgraph "Web Server"
        Apache[Apache/Nginx]
        index[index.php]
    end

    subgraph "Core Framework"
        Router[Router]
        Auth[Authentication]
    end

    subgraph "Controllers"
        Main[MainController]
        AuthC[AuthController]
        InflowC[InflowController]
        OutflowC[OutflowController]
        BudgetC[BudgetController]
        InvestmentC[InvestmentController]
        MoneyLoanC[MoneyLoanController]
        ReportC[ReportController]
    end

    subgraph "Models"
        ORM[Orm Base]
        User[User]
        Inflow[Inflow]
        Outflow[Outflow]
        Budget[Budget]
        Investment[Investment]
        MoneyLoan[MoneyLoan]
    end

    subgraph "Database"
        MySQL[(MySQL)]
    end

    subgraph "Views"
        Layouts[Layouts]
        Pages[Page Views]
        JS[JavaScript]
        CSS[CSS]
    end

    subgraph "Helpers"
        H1[dates]
        H2[redirect]
        H3[validator]
        H4[render_views]
    end

    Browser --> Apache
    Mobile --> Apache
    Apache --> index
    index --> Router
    Router --> Auth
    Router --> Main
    Router --> AuthC
    Router --> InflowC
    Router --> OutflowC
    Router --> BudgetC
    
    Main --> ORM
    AuthC --> ORM
    InflowC --> Inflow
    OutflowC --> Outflow
    BudgetC --> Budget
    
    Inflow --> MySQL
    Outflow --> MySQL
    Budget --> MySQL
    User --> MySQL
    
    Main --> Layouts
    AuthC --> Layouts
    InflowC --> Pages
    OutflowC --> Pages
    
    ORM --> H1
    ORM --> H2
    Auth --> H3
```

---

## Componentes por capa

| Capa | Componentes |
|------|-------------|
| **Controllers** | Main, Auth, Inflow, Outflow, Budget, Investment, MoneyLoan, Report |
| **Models** | User, Inflow, Outflow, Budget, Investment, MoneyLoan, Category, Porcent |
| **Core** | Router, ORM, Authentication, Controller |
| **Helpers** | dates, redirect, validator, pagination, json, render_views |
| **Views** | layouts/, inflows/, outflows/, budgets/, investments/, loans/ |