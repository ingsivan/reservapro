<?php

namespace App\Notifications\Messages;

/**
 * A WhatsApp message built from a template approved in Meta's WhatsApp
 * Manager. Businesses can only start a conversation with templates, so each
 * notification picks one and fills its {{1}}, {{2}}… placeholders in order.
 */
class WhatsAppMessage
{
    /**
     * Templates to create in WhatsApp Manager (category "Utility", language
     * Spanish (MEX)), with the same name and text.
     *
     * @var array<string, string>
     */
    public const TEMPLATES = [
        'cita_confirmada' => 'Hola {{1}}, tu cita en {{2}} está confirmada: {{3}}, {{4}}. Para verla, cambiarla o cancelarla: {{5}}',
        'cita_solicitada' => 'Hola {{1}}, recibimos tu solicitud de cita en {{2}}: {{3}}, {{4}}. Te avisaremos cuando el negocio la confirme. Para verla o cancelarla: {{5}}',
        'recordatorio_cita' => 'Hola {{1}}, te recordamos tu cita en {{2}}: {{3}}, {{4}}. Si no puedes asistir, cámbiala o cancélala aquí: {{5}}',
        'cita_reprogramada' => 'Hola {{1}}, {{2}} cambió el horario de tu cita de {{3}}. Nuevo horario: {{4}}. Si no te funciona, elige otro o cancela aquí: {{5}}',
        'cita_cancelada' => 'Hola {{1}}, {{2}} tuvo que cancelar tu cita de {{3}} del {{4}}. Puedes reservar otro horario aquí: {{5}}',
    ];

    /**
     * @param  list<string>  $parameters
     */
    public function __construct(public readonly string $template, public readonly array $parameters) {}

    /**
     * @param  list<string>  $parameters
     */
    public static function template(string $template, array $parameters): self
    {
        return new self($template, $parameters);
    }

    /**
     * The message as the customer reads it.
     */
    public function text(): string
    {
        $replacements = [];

        foreach ($this->parameters as $index => $value) {
            $replacements['{{'.($index + 1).'}}'] = $value;
        }

        return strtr(self::TEMPLATES[$this->template], $replacements);
    }

    /**
     * Body for the Cloud API "messages" endpoint.
     *
     * @return array<string, mixed>
     */
    public function toPayload(string $to, string $language): array
    {
        return [
            'messaging_product' => 'whatsapp',
            'to' => ltrim($to, '+'),
            'type' => 'template',
            'template' => [
                'name' => $this->template,
                'language' => ['code' => $language],
                'components' => [[
                    'type' => 'body',
                    'parameters' => array_map(fn (string $value) => ['type' => 'text', 'text' => $value], $this->parameters),
                ]],
            ],
        ];
    }
}
