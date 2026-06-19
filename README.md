# Asistente-IA Bundle

Bundle Symfony liviano para mostrar una burbuja de chat IA y delegar la conversacion y la indexacion a un microservicio externo.

## Estructura

- `config/routes.yaml`: rutas del widget y del endpoint JSON
- `config/services.yaml`: registro de servicios
- `src/Controller/WidgetController.php`: render del widget
- `src/Controller/Api/ChatRequestController.php`: endpoint local para el frontend
- `src/Controller/IndexDocumentController.php`: formulario reusable para indexar documentos personalizados
- `src/Service/ExternalAssistantClient.php`: cliente HTTP hacia el microservicio
- `src/Contract/IndexableDocumentInterface.php`: contrato reusable para documentos indexables
- `src/Event/IndexDocumentEvent.php`: evento genérico para disparar indexacion desde cualquier proyecto
- `src/Service/ExternalIndexClient.php`: cliente HTTP para enviar documentos al microservicio de vectorizacion
- `Resources/views/widget/bubble.html.twig`: UI base del chat
- `Resources/public/js/ai-chat-widget.js`: interaccion del widget
- `Resources/public/css/ai-chat-widget.css`: estilos del widget

## Configuracion sugerida en el proyecto host

```yaml
asistente_ia:
  base_url: 'https://mi-microservicio.example'
  chat_endpoint: '/api/chat'
  index_endpoint: '/api/index/documents'
  tenant_name: 'projects'
  api_key: '%env(AI_CHAT_API_KEY)%'
  connect_timeout: 5
  timeout: 30
  default_headers:
    X-App-Name: 'marketing'
```

## Contrato de indexacion reusable

Si una entidad implementa `Maoxtrem\AsistenteIa\Contract\IndexableDocumentInterface`, el proyecto puede enviarla al microservicio con:

```php
$indexClient->indexIndexable($entity);
```

El contrato solo define la forma de los datos. Cada proyecto decide que entidades expone y como arma `source`, `tenant`, `title`, `content` y `metadata`.

Para separar el contexto entre proyectos, configura `tenant_name` desde el `.env` del host. Ese valor se envía al microservicio y se usa para filtrar los documentos vectoriales por proyecto.

Si prefieres un flujo por eventos, puedes despachar `Maoxtrem\AsistenteIa\Event\IndexDocumentEvent` y escuchar ese evento en el proyecto host.

## Integracion en el host

1. Registrar el bundle en `config/bundles.php`
2. Importar `@AsistenteIaBundle/config/routes.yaml`
3. Renderizar `@AsistenteIa/...` desde Twig
4. Publicar o copiar los assets del widget
5. Incluir la vista:

```twig
{{ render(path('asistente_ia_widget')) }}
```

## Formulario de indexacion manual

El bundle incluye una ruta para cargar documentos vectoriales personalizados desde el navegador:

```twig
{{ path('asistente_ia_vector_form') }}
```

Sirve para crear ejemplos como guias de cambio de idioma, FAQs o instrucciones operativas sin tocar codigo del proyecto host.
