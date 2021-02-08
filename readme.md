# Kubernetes - DEVOPS 🧊
@(WebDev)[webdev]

Documnent using languages: portuguese and english
[GCP - Google Cloud Platform](https://console.cloud.google.com/) 
[Como instalar o Google Cloud SDK](https://cloud.google.com/sdk/docs/install#deb)

## Dinâmica "Superficial"
Source: [Introdução ao Kubernetes](https://portal.code.education/lms/#/172/155/94/conteudos?capitulo=645&conteudo=5694)

**Cluster** é um conjunto de máquinas (**Nodes**). Cada máquina possui uma quantidade de vCPU e memória.

- **Pods**: unidade que contém os containers provisionados
- O Pod representa os processos rodando no cluster

![ClusterNodePod|center|500x270](https://matthewpalmer.net/kubernetes-app-developer/articles/networking-overview.png)


##  Services

É uma forma de agregar um conjunto de pods para então implementar políticas de visibilidade.  


### Type of connections
[![Types](https://i.imgur.com/Q4t70QH.jpg)](https://imgur.com/Q4t70QH)



### Sectors

![Selectors|center|400x300](https://i.imgur.com/SsSsyVj.png)


## Minikube

### Initialization
`minikube start`
- or according the article [Minikube on Windows 10 with Hyper-V](https://medium.com/@JockDaRock/minikube-on-windows-10-with-hyper-v-6ef0f4dc158c#:~:text=Open%20the%20Hyper-V%20Manager,the%20Create%20Virtual%20Switch%20button.):
`minikube start --vm-driver hyperv --hyperv-virtual-switch "Primary Virtual Switch"`

## Kubectl

1. Show services `kubectl get svc`
2. Show pods `kubectl get pods`
3. Show deployments `kubectl get deployments`


### Creating a single pod to test
[Source](https://portal.code.education/lms/#/172/155/94/conteudos?capitulo=645&conteudo=5696)
1. `kubectl apply -f pod.yaml`
	```
	# kubectl apply -f pod.yaml
	apiVersion: v1
	kind: Pod
	metadata:
	  name: pod-exemplo
	spec:
	  containers:
	    - name: pod-exemplo
	      image: nginx:1.17-alpine
	```
2. View log of pod by name `kubectl logs pod-exemplo`

### Creating a deployment

#### Using command-line

1. Create a deployment
`kubectl create deployment hello-nginx --image=nginx:1.17-alpine`
	- Check it with `kubectl get deployments`

2. Create a service
`kubectl expose deployment hello-nginx --type=LoadBalancer --port=80`
	- To show it you can run `kubectl get services`

	| NAME | TYPE | CLUSTER-IP | EXTERNAL-IP | PORT(S) 
	|:-----: | :-----:| :-----: | :-----:|:-----: |
	| hello-nginx |LoadBalancer |10.97.138.76 |`pending` | 80:31365/TCP | 
	
3. To test on browser use `minikube service hello-nginx`
	![Browser](https://i.imgur.com/xJ6ksmW.png)

#### Using `deployment.yaml`

1. create a file `deployment.yaml` with
	```yaml
	# kubectl apply -f deployment.yaml
	apiVersion: apps/v1
	kind: Deployment
	metadata:
	  name: hello-nginx
	  labels:
	    app: nginx
	spec:
	  replicas: 1
	  selector:
	    matchLabels:
	      app: hello-nginx
	  template:
	    metadata:
	      labels:
	        app: hello-nginx
	    spec:
	      containers:
	      - name: nginx
	        image: nginx:1.17-alpine
	        ports:
	        - containerPort: 80
	```
2. Delete previous deployment with 
	`kubectl delete deployments --all`
3. `kubectl apply -f deployment.yaml`


### Creating a service
1. create a file `service.yaml` with
	```yaml
	# kubectl apply -f service.yaml
	apiVersion: v1
	kind: Service
	metadata:
	  name: nginx-service
	spec:
	  type: LoadBalancer
	  ports: 
	    - port: 80
	  selector:
	    app: hello-nginx
	```
2. Run `kubectl apply -f service.yaml`
3. Test on command line `kubectl get services`
4. Test on browser with `minikube service nginx-service`


### Using ConfigMap to Update nginx settings

1. create a file `configmap.yaml` with
	```yaml
	# kubectl apply -f configmap.yaml
	apiVersion: v1
	kind: ConfigMap
	metadata:
	  name: nginx-conf
	  labels:
	    app: myapplication
	data:
	  nginx.conf: |
	
	    server {
	      listen 80;
	      index index.php index.html;
	      root /usr/share/nginx/html;
	
	      rewrite ^/google$ http://google.com permanent;
	    }
	```
2. Add new volume and volumeMount to `deployment.yaml`
	```
	spec:
	  containers:
	  - name: nginx
	    image: nginx:1.17-alpine
	    ports:
	    - containerPort: 80
	
	    volumeMounts:
	    - mountPath:  /etc/nginx/conf.d
	      name:  nginx-conf
	      readOnly: true
	
	  volumes:
	  - name: nginx-conf
	    configMap:
	      name: nginx-conf
	      items:
	        - key: nginx.conf
	          path: nginx.conf
	 
	```
3. Test config with `kubectl get configmaps`
4. Test on browser `minikube service nginx-service`

### Add Mysql from zero

1. create a folder `mysql` and a new file `deployment.yaml`
```yaml
# kubectl apply -f deployment.yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name:  mysql-server
  labels:
    name:  mysql-server
spec:
  replicas: 1

  selector: # Selector using to create a Service
    matchLabels:
      app: mysql-server
      tier: db

  template: # POD 
    metadata:
      labels:
        app: mysql-server
        tier: db
    spec: # spec do POD 
      containers:
      - image:  mysql:5.7
        name:  mysql-server
        args: 
          - "--ignore-db-dir=lost+found"

        env:
        - name: MYSQL_ROOT_PASSWORD
          value: root
        ports:
        - containerPort:  3306
        volumeMounts:
         - name: mysql-persistent-storage
           mountPath: /var/lib/mysql
      
      volumes:
      - name: mysql-persistent-storage
        persistentVolumeClaim:
          claimName: mysql-pv-claim


```

2. Creating a persistent volume `persistent-volume.yaml`
	- `kubectl apply -f persistent-volume.yaml`

```
apiVersion: v1
kind: PersistentVolumeClaim
metadata:
  name: mysql-pv-claim
spec:
  accessModes:
  - ReadWriteOnce
  resources:
    requests:
      storage: 10Gi
```

### Create Secret

1. `kubectl create secret generic mysql-pass --from-literal=password='a1s2d3f4'`
2. Check it with `kubectl get secrets`
3. Change `deployment.yaml` to read secret:

```
spec: # spec do POD 
  containers:
  - image:  mysql:5.7
    name:  mysql-server
    args: 
      - "--ignore-db-dir=lost+found"

    env:
    - name: MYSQL_ROOT_PASSWORD
      valueFrom:
        secretKeyRef:
          name: mysql-pass
          key: password
```

### Criando Mysql Service

1. Create a file `service.yaml` with

```
# kubectl apply -f service.yaml
apiVersion: v1
kind: Service
metadata:
  name: mysql-service
spec:
  ports:
    - port: 3306
  selector:
    app: mysql-server
    tier: db
  clusterIP: None
```


2. `kubectl apply -f service.yaml`
3. Check services with `kubectl get svc`
4. `kubectl get pods`
5. Executar pod com `kubectl exec -it mysql-server-866f9b567b-4bp62 bash`
6. Abrir mysql com: `mysql -uroot -p` e senha `a1s2d3f4`

### Apagar volume e deployment
1. `kubectl delete PersistentVolumeClaim mysql-pv-claim` 
2. `kubectl delete deployment mysql`


### Gerenciando recursos

Testing nginx `deployment.yaml`:
- limitar o uso de cpu de cada Pod com: `cpu: "80m"`
	- unidade de medida de uso de vCPU é chamada *milicore* `mCPU` no kubernetes
- No caso da memória se o pode bater o limite `memory: "100Mi"` então ele nem sobe, é bem diferente do caso do cpu que sobe apenas deixando processamento mais lento.
-  resources:
	-  requests:  significa o requisito mínimo para subir o pod
	-  limits: significa o máximo de recursos que pode ser consumido pelo pod
	-  Qualquer coisa além disso o Kubernetes vai tentar duas estratégias:
		1. diminuir a quantidade computacional ou
		2. simplesmente matar o container para não dar um crash


```
# kubectl apply -f deployment.yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: hello-nginx
  labels:
    app: nginx
spec:
  replicas: 2

  selector:
    matchLabels:
      app: hello-nginx
      
  template:
    metadata:
      labels:
        app: hello-nginx
    spec:
      containers:
      - name: nginx
        image: nginx:1.17-alpine
        ports:
        - containerPort: 80

        resources:
          requests:
            memory: "100Mi"
            cpu: "80m"
          limits:
            memory: "150Mi"
            cpu: "100m"

        volumeMounts:
        - mountPath:  /etc/nginx/conf.d
          name:  nginx-conf
          readOnly: true

      volumes:
      - name: nginx-conf
        configMap:
          name: nginx-conf
          items:
            - key: nginx.conf
              path: nginx.conf
 
```

## Autoscaler
1. create a folder `php-apache`
2. create `index.php`
```
<?php

$x = 0.000001;
for ($i = 0; $i <= 100000000; ++$i) {
    $x += sqrt($x);
}
echo  PHP_EOL.'Code.education Rocks! '.PHP_EOL;
```
3. create a Dockerfile
```
# docker build -t lfrichter/php-apache-hpa .
# docker run -it lfrichter/php-apache-hpa bash
# docker push lfrichter/php-apache-hpa
FROM php:7-apache
COPY index.php /var/www/html/index.php
RUN chmod a+rx index.php
```
4. create HPA deployment with
	- `kubectl run php-apache-hpa --image=lfrichter/php-apache-hpa --requests=cpu=200m --expose --port=80`
5. creating command to autoscaling (optional)
	- `kubectl autoscale deployment php-apache-hpa --cpu-percent=20 --min=1 --max=10`
6. creating a file to autoscaling (better choice)
	1. create a file `hpa.yaml` with:
```
# kubectl apply -f hpa.yaml
apiVersion: autoscaling/v1
kind: HorizontalPosAutoscaler
metadata:
  name: php-apache-hpa
spec:
  maxReplicas: 5
  minReplicas: 1
  scaleTargetRef: 
    apiVersion: extension/v1beta1
    kind: Deployment
    name: php-apache-hpa
  targetCPUUtilizationPercentage: 20
```


#### Testing HPA
1. `kubectl run -it loader --image=busybox /bin/sh`