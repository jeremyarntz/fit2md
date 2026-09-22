<?php

declare(strict_types=1);

namespace App\Description;

use App\Description\Exception\UnknownTemplateTypeException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class DescriptionTemplateRegistry
{
    /** @param iterable<DescriptionTemplateInterface> $templates */
    public function __construct(
        #[AutowireIterator('app.description_template')]
        private iterable $templates,
    ) {
    }

    /** @throws UnknownTemplateTypeException */
    public function templateFor(string $type): DescriptionTemplateInterface
    {
        foreach ($this->templates as $template) {
            if ($template->getType() === $type) {
                return $template;
            }
        }

        throw new UnknownTemplateTypeException(sprintf('Unknown workout type "%s". Available types: %s.', $type, implode(', ', $this->types())));
    }

    /** @return list<string> */
    public function types(): array
    {
        $types = [];

        foreach ($this->templates as $template) {
            $types[] = $template->getType();
        }

        return $types;
    }
}
