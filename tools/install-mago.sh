#!/usr/bin/env bash
# Downloads the Mago binary into ./tools/mago.
#
# Reproducible installer used by both local dev and CI. Avoids the
# carthage-software/mago composer wrapper, which ships a broken dist tarball
# (missing composer/src/*.php) as of v1.27.

set -euo pipefail

MAGO_VERSION="${MAGO_VERSION:-1.27.0}"
INSTALL_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEST="${INSTALL_DIR}/mago"

case "$(uname -s)-$(uname -m)" in
  Linux-x86_64)   asset="mago-${MAGO_VERSION}-x86_64-unknown-linux-gnu.tar.gz" ;;
  Linux-aarch64)  asset="mago-${MAGO_VERSION}-arm-unknown-linux-gnueabi.tar.gz" ;;
  Darwin-x86_64)  asset="mago-${MAGO_VERSION}-x86_64-apple-darwin.tar.gz" ;;
  Darwin-arm64)   asset="mago-${MAGO_VERSION}-aarch64-apple-darwin.tar.gz" ;;
  *)
    echo "Unsupported platform: $(uname -s)-$(uname -m)" >&2
    exit 1
    ;;
esac

url="https://github.com/carthage-software/mago/releases/download/${MAGO_VERSION}/${asset}"
tmp="$(mktemp -d)"
trap 'rm -rf "${tmp}"' EXIT

echo "Downloading ${asset}..."
curl -fsSL -o "${tmp}/mago.tar.gz" "${url}"
tar -xzf "${tmp}/mago.tar.gz" -C "${tmp}"
mv "${tmp}/"*"/mago" "${DEST}"
chmod +x "${DEST}"

echo "Installed: $(${DEST} --version)"
