# Asistente-IA Bundle

Bundle Symfony liviano para mostrar una burbuja de chat IA y delegar la conversacion a un microservicio externo.

## Estructura

- `config/routes.yaml`: rutas del widget y del endpoint JSON
- `config/services.yaml`: registro de servicios
- `src/Controller/WidgetController.php`: render del widget
- `src/Controller/Api/ChatRequestController.php`: endpoint local para el frontend
- `src/Service/ExternalAssistantClient.php`: cliente HTTP hacia el microservicio
- `Resources/views/widget/bubble.html.twig`: UI base del chat
- `Resources/public/js/ai-chat-widget.js`: interaccion del widget
- `Resources/public/css/ai-chat-widget.css`: estilos del widget

## Configuracion sugerida en el proyecto host

```yaml
asistente_ia:
  base_url: 'https://mi-microservicio.example'
  chat_endpoint: '/api/chat'
  api_key: '%env(AI_CHAT_API_KEY)%'
  connect_timeout: 5
  timeout: 30
  default_headers:
    X-App-Name: 'marketing'
```

## Integracion en el host

1. Registrar el bundle en `config/bundles.php`
2. Importar `@AsistenteIaBundle/config/routes.yaml`
3. Renderizar `@AsistenteIa/...` desde Twig
4. Publicar o copiar los assets del widget
5. Incluir la vista:

```twig
{{ render(path('asistente_ia_widget')) }}
```
