# Steps
 - Check the file .htaccess.
 - RewriteBase / -> Or change in your path of the domain.

 - Check the file main.js 'public/assets/js/main.js' por replace the URL of the back-end or server
 - Add params for the configuration with the dabase in app/config/config.php


# Comandos Kubernetes deploy:

## Refrescar cluster en el deploy de finanzas
k3s kubectl rollout restart -n prod deployment/manejo-finanzas

## Ver dentro del de pod de ese deploy terminal
k3s kubectl exec -it deployment/manejo-finanzas -n prod -- /bin/sh

## Importar BD
mariadb -u root -p < /home/lobaton/servidor_completo.sql

## Delete Secret
k3s kubectl delete secret manejo-finanzas-secrets -n prod

## Create secret for DB
 k3s kubectl create secret generic manejo-finanzas-secrets --from-file=.env=./.env -n prod

## Crear imagen de docket temporal
k3s kubectl run mariadb-test --rm -it --image=mariadb:10.11 -- bash

## Aplicar deployment
k3s kubectl apply -k deploy/overlays/prod

## Listar todos los deployments
k3s kubectl get deployments -A -n prod

## Ver logs de un deploy
k3s kubectl logs manejo-finanzas-977dbdf6c-ptqx -n prod

## logs del pod en cuanto a su gestion

## Eliminar todos los pods
k3s kubectl delete pods --all -n prod

## Listar deployments
kubectl get deployments -n prod

## Eliminar deployment
delete deployment manejo-finanzas -n prod

## ver servicioS
kubectl get services -n prod

## ver todo de un namespace:
k3s kubectl get all -n prod

## Eliminar todo de un namespace
k3s kubectl delete all --all -n prod

## Actualizar IP cluster service.
k3s kubectl patch svc manejo-finanzas -p '{"metadata":{"annotations":{"metallb.universe.tf/loadBalancerIP":"192.168.20.21"}}}' -n prod
