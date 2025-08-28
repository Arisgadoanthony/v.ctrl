<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Breeze & Leaves Effect</title>
    <style>
        /* Basic styling for the body */
        body {
            margin: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #111; /* Dark background for contrast */
            overflow: hidden; /* Hide scrollbars */
            cursor: none; /* Hide default mouse cursor for a cleaner effect */
            touch-action: none; /* Disable touch gestures like scrolling/zooming on touch devices */
        }

        /* Container for the light effect and leaves */
        .light-container {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
            /* Ensures full coverage and correct positioning */
        }

        /* The light effect element */
        .light {
            position: absolute;
            width: 150px;
            height: 150px;
            /* Radial gradient creates a soft, glowing light */
            background: radial-gradient(circle, rgba(255, 255, 200, 0.6) 0%, rgba(255, 255, 200, 0.2) 50%, transparent 80%);
            border-radius: 50%; /* Makes it circular */
            pointer-events: none; /* Allows clicks/interactions to pass through */
            transform: translate(-50%, -50%); /* Centers the light element on its coordinates */
            mix-blend-mode: screen; /* Blends colors nicely with the background */
            transition: opacity 0.2s ease; /* Smooth fade in/out transition */
            opacity: 0; /* Hidden by default */
        }

        /* Show the light effect when hovering over its container */
        .light-container:hover .light {
            opacity: 1; /* Make it visible */
        }

        /* Styling for the individual leaf particles */
        .leaf {
            position: absolute;
            width: 15px; /* Width of the leaf */
            height: 8px; /* Height of the leaf */
            background-color: #8BC34A; /* Yellow-green color for leaves */
            border-radius: 0 50% 50% 50%; /* Creates a simple leaf-like shape */
            transform-origin: 0% 50%; /* Sets rotation pivot to one end for a natural look */
            pointer-events: none; /* Allows interactions to pass through */
            will-change: transform, opacity; /* Optimizes browser rendering for smooth animations */
        }
    </style>
</head>
<body>
    <div class="light-container" id="lightArea">
        <div class="light" id="light"></div>
    </div>

    <script>
        const light = document.getElementById("light");
        const container = document.getElementById("lightArea");
        let leaves = []; // Array to hold active leaf objects
        const maxLeaves = 100; // Maximum number of leaves on screen at any time
        const leafCreationInterval = 50; // Milliseconds between new leaf creations (throttling)
        let lastLeafCreationTime = 0; // Timestamp of the last leaf creation

        // Constants for leaf animation
        const leafPopInDuration = 500; // ms
        const leafActiveDuration = 1500; // ms
        const leafFadeOutDuration = 1000; // ms
        const leafTotalDuration = leafPopInDuration + leafActiveDuration + leafFadeOutDuration;

        /**
         * Creates a new leaf element and adds it to the leaves array.
         * @param {number} x - Initial X coordinate for the leaf.
         * @param {number} y - Initial Y coordinate for the leaf.
         */
        function createLeaf(x, y) {
            const leafElement = document.createElement("div");
            leafElement.classList.add("leaf");
            container.appendChild(leafElement);

            // Random initial properties for variety
            const initialRotation = Math.random() * 360; // Random starting rotation
            const speedMultiplier = 0.5 + Math.random() * 1.5; // Varied speed
            const directionAngle = Math.random() * 2 * Math.PI; // Random initial direction
            const vx = Math.cos(directionAngle) * speedMultiplier; // Velocity X
            const vy = Math.sin(directionAngle) * speedMultiplier; // Velocity Y
            const rotationSpeed = (Math.random() - 0.5) * 4; // Speed of rotation

            // Random delay for when the leaf starts its animation
            const delay = Math.random() * 1000; // Up to 1 second delay

            leaves.push({
                element: leafElement,
                x: x,
                y: y,
                vx: vx,
                vy: vy,
                rotation: initialRotation,
                rotationSpeed: rotationSpeed,
                creationTime: performance.now(), // Timestamp when this leaf was created
                delay: delay, // Individual delay for each leaf
                duration: leafTotalDuration // Total duration of the leaf's life
            });
        }

        /**
         * Animation loop for updating leaf positions and opacities.
         * Uses requestAnimationFrame for smooth animations.
         */
        function animateLeaves() {
            const now = performance.now();

            // Iterate backwards to safely remove elements during iteration
            for (let i = leaves.length - 1; i >= 0; i--) {
                const leaf = leaves[i];
                const element = leaf.element;
                const elapsedTime = now - leaf.creationTime; // Time since leaf was created

                // If the leaf's total duration is over, remove it
                if (elapsedTime > leaf.delay + leaf.duration) {
                    element.remove();
                    leaves.splice(i, 1);
                    continue; // Move to the next leaf
                }

                // Only animate if the leaf's individual delay has passed
                if (elapsedTime > leaf.delay) {
                    const activeTime = elapsedTime - leaf.delay; // Time since animation actually started

                    // Update position
                    leaf.x += leaf.vx;
                    leaf.y += leaf.vy;
                    leaf.rotation += leaf.rotationSpeed;

                    // Calculate opacity and scale based on its animation phase
                    let opacity = 0;
                    let scale = 0;

                    if (activeTime < leafPopInDuration) {
                        // Popping in phase
                        const progress = activeTime / leafPopInDuration;
                        opacity = progress;
                        scale = progress * 1.1; // Slight overshoot for pop effect
                    } else if (activeTime < leafPopInDuration + leafActiveDuration) {
                        // Active phase
                        opacity = 1;
                        scale = 1;
                    } else {
                        // Fading out phase
                        const fadeOutProgress = (activeTime - (leafPopInDuration + leafActiveDuration)) / leafFadeOutDuration;
                        opacity = 1 - fadeOutProgress;
                        scale = 1 - (fadeOutProgress * 0.5); // Shrink slightly on fade out
                    }

                    // Apply styles
                    element.style.left = `${leaf.x}px`;
                    element.style.top = `${leaf.y}px`;
                    element.style.opacity = opacity;
                    element.style.transform = `translate(-50%, -50%) rotate(${leaf.rotation}deg) scale(${scale})`;
                }
            }
            requestAnimationFrame(animateLeaves); // Loop the animation
        }

        /**
         * Handles mouse movement to update light position and create leaves.
         * @param {MouseEvent|TouchEvent} e - The event object.
         */
        function handlePointerMove(e) {
            let clientX, clientY;
            if (e.touches && e.touches.length > 0) {
                clientX = e.touches[0].clientX;
                clientY = e.touches[0].clientY;
            } else {
                clientX = e.clientX;
                clientY = e.clientY;
            }

            const rect = container.getBoundingClientRect();
            const x = clientX - rect.left;
            const y = clientY - rect.top;

            light.style.left = `${x}px`;
            light.style.top = `${y}px`;

            const now = performance.now();
            // Throttle leaf creation and limit total leaves
            if (now - lastLeafCreationTime > leafCreationInterval && leaves.length < maxLeaves) {
                // Create leaf slightly scattered around the pointer
                createLeaf(x + (Math.random() * 30 - 15), y + (Math.random() * 30 - 15));
                lastLeafCreationTime = now;
            }
        }

        // Add event listeners for mouse and touch
        container.addEventListener("mousemove", handlePointerMove);
        container.addEventListener("touchmove", handlePointerMove, { passive: true }); // Use passive for better scroll performance on mobile
        
        // Handle touchstart and touchend to manage light opacity on touch devices
        container.addEventListener("touchstart", (e) => {
            light.style.opacity = 1;
            handlePointerMove(e); // Initial position for touch
        });
        container.addEventListener("touchend", () => {
            light.style.opacity = 0;
        });


        // Initial call to start the animation loop
        animateLeaves();
    </script>
</body>
</html>
