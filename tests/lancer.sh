#!/bin/sh
# Lance les tests du thème dans un WordPress neuf (WordPress Playground, Node.js nécessaire).
# Usage : tests/lancer.sh [version de PHP] [version de WordPress]   (par défaut : 8.3 et latest)
# Exemple, les versions minimales annoncées : tests/lancer.sh 7.4 6.6
cd "$(dirname "$0")/.." || exit 1
rm -f tests/resultats.txt
# Le Playground ne lit les versions que dans le blueprint : on en écrit une copie avec celles demandées.
plan=$(mktemp -t blueprint).json
node -e '
	const b = require( process.argv[1] );
	b.preferredVersions = { php: process.argv[2], wp: process.argv[3] };
	require( "fs" ).writeFileSync( process.argv[4], JSON.stringify( b ) );
' "$PWD/tests/blueprint.json" "${1:-8.3}" "${2:-latest}" "$plan" || exit 1
statut=0
npx -y @wp-playground/cli@latest run-blueprint --blueprint="$plan" \
	--mount=.:/wordpress/wp-content/themes/les-scouts-unite || statut=$?
rm -f "$plan"
cat tests/resultats.txt 2>/dev/null || echo "Pas de résultats : le WordPress de test s'est arrêté avant les tests."
exit $statut
