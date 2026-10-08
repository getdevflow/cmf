<?php

declare(strict_types=1);

namespace Theme\IntegrationParent {
    class IntegrationParentTheme extends \Theme\Demo\DemoTheme
    {
    }
}

namespace Theme\IntegrationChild {
    final class IntegrationChildTheme extends \Theme\IntegrationParent\IntegrationParentTheme
    {
    }
}
