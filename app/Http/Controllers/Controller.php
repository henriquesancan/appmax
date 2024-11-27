<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\Response;

abstract class Controller
{
    /**
     * Retorna os cabeçalhos padrão para as respostas JSON.
     *
     * @return array
     */
    protected function headers(): array
    {
        return [
            'Content-Type' => 'application/json; charset=UTF-8'
        ];
    }

    /**
     * Retorna um array com a estrutura padrão para respostas JSON.
     *
     * @param int $status O código de status HTTP para a resposta.
     * @param bool $success Indica se a operação foi bem-sucedida.
     * @return object
     */
    protected function response(int $status = Response::HTTP_INTERNAL_SERVER_ERROR, bool $success = false): object
    {
        return (object) [
            'status' => $status,
            'success' => $success,
            'errors' => [],
            'data' => [],
        ];
    }
}
