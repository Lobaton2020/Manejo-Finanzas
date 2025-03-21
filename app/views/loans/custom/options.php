<?php
return "<div>
    <a class='dropdown-item' href='". route('moneyLoan/createListenerNotification/:id_money_loan') ."'> Editar notificacion </a>
    <a class='dropdown-item question' href='". route('moneyLoan/disable/:id_money_loan') ."'> Inactivar </a>
    <a class='dropdown-item question' href='".route('moneyLoan/delete/:id_money_loan') ."'> Eliminar </a>
</div>";