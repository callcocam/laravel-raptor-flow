# laravel-raptor-flow

[![Latest Version on Packagist](https://img.shields.io/packagist/v/callcocam/laravel-raptor-flow.svg?style=flat-square)](https://packagist.org/packages/callcocam/laravel-raptor-flow)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/callcocam/laravel-raptor-flow/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/callcocam/laravel-raptor-flow/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/callcocam/laravel-raptor-flow/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/callcocam/laravel-raptor-flow/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/callcocam/laravel-raptor-flow.svg?style=flat-square)](https://packagist.org/packages/callcocam/laravel-raptor-flow)

Backend-driven workflow + kanban toolkit for Laravel applications.

The package provides:

- `FlowManager` for workflow lifecycle (configure, start, move, pause, resume, assign, abandon, notes)
- `KanbanService` for a canonical board payload
- Display and action DSLs for backend-defined modal/card rendering
- Vue plugin (`FlowRaptorPlugin`) with registries for action/display components

## Documentation

- [Docs Index](docs/INDEX.md)
- [Implementation Overview](docs/IMPLEMENTATION.md)
- [Usage Guide](docs/USAGE.md)
- [Authorization](docs/AUTHORIZATION.md)
- [Migration Guide](docs/MIGRATION.md)
- [Workflow and Kanban Contracts](docs/WORKFLOW-MODELS-KANBAN.md)

## Installation

Install via Composer:

```bash
composer require callcocam/laravel-raptor-flow
```

Publish migrations and run them:

```bash
php artisan vendor:publish --tag="laravel-raptor-flow-migrations"
php artisan migrate
```

Publish config:

```bash
php artisan vendor:publish --tag="laravel-raptor-flow-config"
```

## Usage

### Build board payload

```php
use Callcocam\LaravelRaptorFlow\Services\KanbanService;
use Callcocam\LaravelRaptorFlow\Support\Kanban\Columns\Types\WorkableColumn;

$service = app(KanbanService::class)
		->setFlow($flow)
		->setWorkableType(App\Models\Workflow\GondolaWorkflow::class)
		->setWorkableIds(fn () => $workableIds)
		->setFilters($filters)
		->addColumn(WorkableColumn::make('workable'));

$payload = $service->getBoardData();
// ['board', 'groupConfigs', 'userRoles', 'filters', 'cardConfig']
```

### Build detail modal config

```php
use Callcocam\LaravelRaptorFlow\Support\Actions\CustomAction;
use Callcocam\LaravelRaptorFlow\Support\Display\DisplayField;
use Callcocam\LaravelRaptorFlow\Support\Display\DisplaySection;
use Callcocam\LaravelRaptorFlow\Support\Display\NotesBlock;

$service
		->withDetailModal()
		->addAction(
				CustomAction::make('start')
						->label('Iniciar')
						->icon('Play')
						->url('/workflow/gondolas/{workable.id}/start')
						->variant('default')
						->defaultComponent()
		)
		->addSection(
				DisplaySection::make('summary')
						->label('Resumo')
						->addField(DisplayField::badge('status'))
		)
		->addNote(
				NotesBlock::make('workflow-notes')
						->url('/workflow/gondolas/{workable.id}/notes')
						->placeholder('Adicionar notas')
		);

$detailModalConfig = $service->getDetailModalConfig();
```

### Frontend plugin and custom renderers

```ts
import { FlowRaptorPlugin } from '@flow'

app.use(FlowRaptorPlugin, {
	overrideActions: {
		'flow-action-button': MyActionButton,
	},
	overrideDisplayComponents: {
		'flow-display-badge': MyBadgeRenderer,
	},
})
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Claudio Campos](https://github.com/callcocam)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
