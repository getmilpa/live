<p align="center">
  <a href="https://github.com/getmilpa">
    <picture>
      <source media="(prefers-color-scheme: dark)" srcset="https://raw.githubusercontent.com/getmilpa/core/main/art/lockup/milpa-lockup-v-color-dark.svg">
      <img src="https://raw.githubusercontent.com/getmilpa/core/main/art/lockup/milpa-lockup-v-color-light.svg" alt="Milpa" width="300">
    </picture>
  </a>
</p>

# Milpa Live

> **Render-target-agnostic live components** for the Milpa PHP framework — the same
> component definition renders to **web AND terminal**; state, data sources, and an
> event-driven interception seam, no HTML or ANSI in the component itself.

[![CI](https://github.com/getmilpa/live/actions/workflows/ci.yml/badge.svg)](https://github.com/getmilpa/live/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/milpa/live.svg)](https://packagist.org/packages/milpa/live)
[![PHP](https://img.shields.io/badge/php-%E2%89%A5%208.3-777bb4.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-Apache--2.0-blue.svg)](LICENSE)
[![Docs](https://img.shields.io/badge/docs-API%20reference-blue.svg)](https://getmilpa.github.io/live/)

`milpa/live` is the render-target-agnostic core of Milpa's live component system: a
component owns its **contract** (props/state schema, declared actions), its **initial
state** (`mount()`), and how it reacts to client-originated actions (`handle()`) — but
never how it turns into markup or terminal output. That's a
[`ComponentRendererInterface`](src/Contracts/Rendering/ComponentRendererInterface.php)'s
job, paired with the component at the call site. One component, any number of renderers.

## Install

```bash
composer require milpa/live
```

## Quick example

A minimal component plus two renderers — one for HTML, one for TUI — sharing the exact
same `mount()`/`handle()` logic:

```php
use Milpa\Live\Contracts\Component\ComponentDefinitionInterface;
use Milpa\Live\Contracts\Rendering\ComponentRendererInterface;
use Milpa\Live\ValueObjects\{
    ComponentContext, ComponentContract, InteractionRequest,
    InteractionResult, RenderRequest, RenderResult, RenderTarget, StateSnapshot,
};

final class CounterComponent implements ComponentDefinitionInterface
{
    public static function contract(): ComponentContract
    {
        return new ComponentContract(name: 'counter', contractVersion: '1.0.0', actions: ['increment' => []]);
    }

    public function mount(array $props, ComponentContext $context): StateSnapshot
    {
        return new StateSnapshot(
            componentId: $context->componentId,
            componentName: 'counter',
            version: '1.0.0',
            data: ['count' => (int) ($props['start'] ?? 0)],
        );
    }

    public function handle(InteractionRequest $request): InteractionResult
    {
        return new InteractionResult(state: new StateSnapshot(
            componentId: $request->state->componentId,
            componentName: $request->state->componentName,
            version: $request->state->version,
            data: ['count' => $request->state->data['count'] + 1],
        ));
    }
}

final class HtmlCounterRenderer implements ComponentRendererInterface
{
    public function supportsTarget(RenderTarget $target): bool
    {
        return $target === RenderTarget::HTML;
    }

    public function render(ComponentDefinitionInterface $component, RenderRequest $request): RenderResult
    {
        $state = $request->state ?? $component->mount($request->props, $request->context);

        return new RenderResult(
            output: sprintf('<button data-count="%d">Count: %d</button>', $state->data['count'], $state->data['count']),
            state: $state,
            format: RenderTarget::HTML,
        );
    }
}

final class TuiCounterRenderer implements ComponentRendererInterface
{
    public function supportsTarget(RenderTarget $target): bool
    {
        return $target === RenderTarget::TUI;
    }

    public function render(ComponentDefinitionInterface $component, RenderRequest $request): RenderResult
    {
        $state = $request->state ?? $component->mount($request->props, $request->context);

        return new RenderResult(output: "[ Count: {$state->data['count']} ]", state: $state, format: RenderTarget::TUI);
    }
}

$component = new CounterComponent();
$context = new ComponentContext('demo-1');

$html = (new HtmlCounterRenderer())->render($component, new RenderRequest(context: $context, target: RenderTarget::HTML));
echo $html->output; // <button data-count="0">Count: 0</button>

$tui = (new TuiCounterRenderer())->render($component, new RenderRequest(context: $context, target: RenderTarget::TUI));
echo $tui->output; // [ Count: 0 ]
```

`CounterComponent` never printed a single tag or escape code — both renderers turned the
exact same `StateSnapshot` into their own output, independently.

## Web + TUI from one component

That's the thesis this package is built around: **a component definition is a pure
description of state and behavior; rendering is a separate, swappable concern.** A
`ComponentRendererInterface` declares which [`RenderTarget`](src/ValueObjects/RenderTarget.php)(s)
it supports (`HTML`, `TUI`, or the forward-looking `ANSI`) and turns a mounted
`StateSnapshot` into output for that target — nothing in `ComponentDefinitionInterface`
ever needs to know which renderer, or how many, will consume it.

`milpa/live` ships the component contracts, the mount/handle lifecycle, data sources, and
the event-driven interception seam (`component.mounting`/`mounted`,
`component.handling`/`handled`, `component.rendering`/`rendered` — see
[`LiveEventEmitter`](src/Events/LiveEventEmitter.php)) — but **no HTML and no ANSI
renderer**. The web surface (`AutocompleteHtmlRenderer` and friends) lives in
`milpa/live-web`; a TUI renderer is a live candidate in the Milpa lab. This package is
the seam both build on, not either surface itself.

## Requirements

- PHP **≥ 8.3**
- `milpa/core` **^0.5**
- `psr/log` **^3**

## Documentation

**Full API reference: [getmilpa.github.io/live](https://getmilpa.github.io/live/)** —
generated straight from the source DocBlocks and dressed with the Milpa design system.

## Contributing

Contributions are welcome — see [CONTRIBUTING.md](CONTRIBUTING.md). Please report security
issues via [SECURITY.md](SECURITY.md), and note that this project follows a
[Code of Conduct](CODE_OF_CONDUCT.md).

## License

[Apache-2.0](LICENSE) © Rodrigo Vicente - TeamX Agency.

---

Milpa is designed, built, and maintained by **[Rodrigo Vicente - TeamX Agency](https://teamx.agency/?utm_source=github&utm_medium=readme&utm_campaign=milpa&utm_content=live)**.
