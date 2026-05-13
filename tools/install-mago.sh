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
  Darwin-x86_64)  asset="mago-${MAGO_VERSION}-x86_64-apple-darwin.tar.gz" ;;
  Darwin-arm64)   asset="mago-${MAGO_VERSION}-aarch64-apple-darwin.tar.gz" ;;
  Linux-aarch64)
    # Mago v1.27.x releases only ship 32-bit ARM Linux binaries (arm-*).
    # If you're on 64-bit ARM Linux (e.g. Graviton, Pi 4 64-bit), install
    # via `cargo install mago` or pin to a version that ships aarch64.
    echo "Linux aarch64 not supported by Mago v${MAGO_VERSION} prebuilt binaries." >&2
    echo "Install via 'cargo install mago' or use a version with aarch64 assets." >&2
    exit 1
    ;;
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
