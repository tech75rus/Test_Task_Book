<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class ApiKeyListener
{
    public function onKernelRequest(RequestEvent $event): void
    {

        // dd('ApiKeyListener works!');
        // 1. Получаем текущий запрос
        $request = $event->getRequest();
        
        // 2. Проверяем только пути начинающиеся с /api
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return; // Не API путь - пропускаем
        }
        
        // 3. Получаем заголовок X-API-User-Name
        $apiKey = $request->headers->get('X-API-User-Name');
        
        // 4. Проверяем значение
        if ($apiKey !== 'admin') {
            // Создаем ответ с ошибкой 403
            $response = new JsonResponse([
                'error' => 'Access Denied',
                'message' => 'Invalid or missing X-API-User-Name header. Use: admin'
            ], 403);
            
            // Останавливаем выполнение и возвращаем ошибку
            $event->setResponse($response);
        }        
    }
}
