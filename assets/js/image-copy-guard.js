(function () {
    "use strict";

    if (window.KaiImageCopyGuardInitialized) {
        return;
    }
    window.KaiImageCopyGuardInitialized = true;

    function isImageTarget(target) {
        if (!(target instanceof Element)) {
            return false;
        }
        return target.tagName === "IMG" || !!target.closest("img");
    }

    function hardenImage(img) {
        if (!(img instanceof HTMLImageElement)) {
            return;
        }
        img.setAttribute("draggable", "false");
        img.classList.add("ks-img-guard");
    }

    function hardenAllImages(root) {
        var scope = root instanceof Element || root instanceof Document ? root : document;
        var images = scope.querySelectorAll("img");
        images.forEach(hardenImage);
    }

    function injectGuardStyles() {
        if (document.getElementById("ks-img-copy-guard-style")) {
            return;
        }
        var style = document.createElement("style");
        style.id = "ks-img-copy-guard-style";
        style.textContent = [
            "img.ks-img-guard {",
            "  -webkit-user-drag: none;",
            "  user-select: none;",
            "  -webkit-touch-callout: none;",
            "}"
        ].join("\n");
        document.head.appendChild(style);
    }

    function selectionContainsImage() {
        if (typeof window.getSelection !== "function") {
            return false;
        }
        var selection = window.getSelection();
        if (!selection || selection.rangeCount <= 0) {
            return false;
        }

        for (var i = 0; i < selection.rangeCount; i++) {
            var fragment = selection.getRangeAt(i).cloneContents();
            if (fragment && typeof fragment.querySelector === "function" && fragment.querySelector("img")) {
                return true;
            }
        }
        return false;
    }

    function initGuards() {
        injectGuardStyles();
        hardenAllImages(document);

        document.addEventListener("dragstart", function (event) {
            if (isImageTarget(event.target)) {
                event.preventDefault();
            }
        }, true);

        document.addEventListener("contextmenu", function (event) {
            if (isImageTarget(event.target)) {
                event.preventDefault();
            }
        }, true);

        document.addEventListener("selectstart", function (event) {
            if (isImageTarget(event.target)) {
                event.preventDefault();
            }
        }, true);

        document.addEventListener("copy", function (event) {
            if (selectionContainsImage()) {
                event.preventDefault();
            }
        }, true);

        if (typeof MutationObserver === "function" && document.body) {
            var observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    mutation.addedNodes.forEach(function (node) {
                        if (!(node instanceof Element)) {
                            return;
                        }
                        if (node.tagName === "IMG") {
                            hardenImage(node);
                            return;
                        }
                        if (typeof node.querySelectorAll === "function") {
                            node.querySelectorAll("img").forEach(hardenImage);
                        }
                    });
                });
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initGuards, { once: true });
    } else {
        initGuards();
    }
})();
