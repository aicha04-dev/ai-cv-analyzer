import { useEffect, useRef } from "react";
import "./DotField.css";

function DotField({
  dotRadius = 1.5,
  dotSpacing = 14,
  cursorRadius = 500,
  cursorForce = 0.1,
  bulgeOnly = true,
  bulgeStrength = 67,
  glowRadius = 160,
  sparkle = false,
  waveAmplitude = 0,
}) {
  const canvasRef = useRef(null);

  useEffect(() => {
    const canvas = canvasRef.current;

    if (!canvas) {
      return;
    }

    const ctx =
      canvas.getContext("2d");

    let width = 0;
    let height = 0;

    let mouseX = -1000;
    let mouseY = -1000;

    let animationFrame;

    function resize() {
      const rect =
        canvas.getBoundingClientRect();

      width = rect.width;
      height = rect.height;

      const dpr =
        Math.min(
          window.devicePixelRatio || 1,
          2
        );

      canvas.width =
        width * dpr;

      canvas.height =
        height * dpr;

      ctx.setTransform(
        dpr,
        0,
        0,
        dpr,
        0,
        0
      );
    }

    function handleMouseMove(event) {
      mouseX = event.clientX;
      mouseY = event.clientY;
    }

    function handleMouseLeave() {
      mouseX = -1000;
      mouseY = -1000;
    }

    function draw(time) {
      ctx.clearRect(
        0,
        0,
        width,
        height
      );

      const radius =
        dotRadius;

      const spacing =
        dotSpacing;

      for (
        let y = 0;
        y <= height + spacing;
        y += spacing
      ) {
        for (
          let x = 0;
          x <= width + spacing;
          x += spacing
        ) {

          const dx =
            x - mouseX;

          const dy =
            y - mouseY;

          const distance =
            Math.sqrt(
              dx * dx +
              dy * dy
            );

          let size =
            radius;

          let opacity =
            0.22;

          let offsetX = 0;
          let offsetY = 0;

          if (
            distance <
            cursorRadius
          ) {
            const influence =
              1 -
              distance /
                cursorRadius;

            const eased =
              influence *
              influence;

            if (
              bulgeOnly
            ) {
              size +=
                eased *
                (bulgeStrength /
                  35);
            } else {
              size +=
                eased *
                (bulgeStrength /
                  35);

              offsetX =
                dx *
                eased *
                cursorForce *
                -1;

              offsetY =
                dy *
                eased *
                cursorForce *
                -1;
            }

            opacity +=
              eased *
              0.4;
          }

          if (
            glowRadius >
              0 &&
            distance <
              glowRadius
          ) {
            const glow =
              1 -
              distance /
                glowRadius;

            opacity +=
              glow *
              0.2;
          }

          if (sparkle) {
            const sparkleValue =
              Math.sin(
                time * 0.003 +
                x * 0.03 +
                y * 0.02
              );

            opacity +=
              Math.max(
                0,
                sparkleValue
              ) *
              0.12;
          }

          if (
            waveAmplitude >
              0
          ) {
            offsetY +=
              Math.sin(
                x * 0.01 +
                time * 0.001
              ) *
              waveAmplitude;
          }

          ctx.beginPath();

          ctx.arc(
            x + offsetX,
            y + offsetY,
            size,
            0,
            Math.PI * 2
          );

          ctx.fillStyle =
            `rgba(216, 180, 254, ${Math.min(
              opacity,
              0.85
            )})`;

          ctx.fill();
        }
      }

      animationFrame =
        requestAnimationFrame(
          draw
        );
    }

    resize();

    window.addEventListener(
      "resize",
      resize
    );

    window.addEventListener(
      "mousemove",
      handleMouseMove
    );

    window.addEventListener(
      "mouseleave",
      handleMouseLeave
    );

    animationFrame =
      requestAnimationFrame(
        draw
      );

    return () => {
      cancelAnimationFrame(
        animationFrame
      );

      window.removeEventListener(
        "resize",
        resize
      );

      window.removeEventListener(
        "mousemove",
        handleMouseMove
      );

      window.removeEventListener(
        "mouseleave",
        handleMouseLeave
      );
    };
  }, [
    dotRadius,
    dotSpacing,
    cursorRadius,
    cursorForce,
    bulgeOnly,
    bulgeStrength,
    glowRadius,
    sparkle,
    waveAmplitude,
  ]);

  return (
    <canvas
      ref={canvasRef}
      className="dot-field"
    />
  );
}

export { DotField };
export default DotField;