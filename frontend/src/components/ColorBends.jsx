import { useEffect, useRef } from "react";
import * as THREE from "three";
import "./ColorBends.css";
function ColorBends({
  color = "#A855F7",
  speed = 0.2,
  frequency = 1,
  noise = 0.15,
  bandWidth = 0.14,
  rotation = 90,
  fadeTop = 0.75,
  iterations = 1,
  intensity = 1.3,
}) {
  const containerRef = useRef(null);

  useEffect(() => {
    const container = containerRef.current;

    if (!container) {
      return;
    }

    const scene = new THREE.Scene();

    const camera = new THREE.OrthographicCamera(
      -1,
      1,
      1,
      -1,
      0,
      1
    );

    const renderer = new THREE.WebGLRenderer({
      antialias: true,
      alpha: true,
    });

    renderer.setPixelRatio(
      Math.min(window.devicePixelRatio, 2)
    );

    renderer.setSize(
      container.clientWidth,
      container.clientHeight
    );

    container.appendChild(renderer.domElement);

    const uniforms = {
      uTime: { value: 0 },
      uColor: {
        value: new THREE.Color(color),
      },
      uSpeed: { value: speed },
      uFrequency: { value: frequency },
      uNoise: { value: noise },
      uBandWidth: { value: bandWidth },
      uRotation: {
        value: THREE.MathUtils.degToRad(rotation),
      },
      uFadeTop: { value: fadeTop },
      uIterations: { value: iterations },
      uIntensity: { value: intensity },
    };

    const material = new THREE.ShaderMaterial({
      uniforms,

      vertexShader: `
        varying vec2 vUv;

        void main() {
          vUv = uv;

          gl_Position = vec4(
            position,
            1.0
          );
        }
      `,

      fragmentShader: `
        precision highp float;

        varying vec2 vUv;

        uniform float uTime;
        uniform vec3 uColor;
        uniform float uSpeed;
        uniform float uFrequency;
        uniform float uNoise;
        uniform float uBandWidth;
        uniform float uRotation;
        uniform float uFadeTop;
        uniform float uIterations;
        uniform float uIntensity;

        mat2 rotate2D(float angle) {
          float s = sin(angle);
          float c = cos(angle);

          return mat2(
            c, -s,
            s, c
          );
        }

        float hash(vec2 p) {
          return fract(
            sin(
              dot(
                p,
                vec2(
                  127.1,
                  311.7
                )
              )
            ) * 43758.5453
          );
        }

        float noise2D(vec2 p) {
          vec2 i = floor(p);
          vec2 f = fract(p);

          f = f * f * (
            3.0 - 2.0 * f
          );

          float a = hash(i);
          float b = hash(i + vec2(1.0, 0.0));
          float c = hash(i + vec2(0.0, 1.0));
          float d = hash(i + vec2(1.0, 1.0));

          return mix(
            mix(a, b, f.x),
            mix(c, d, f.x),
            f.y
          );
        }

        void main() {

          vec2 uv = vUv;

          vec2 centered =
            uv - 0.5;

          centered =
            rotate2D(uRotation)
            * centered;

          float n =
            noise2D(
              centered *
              (3.0 + uFrequency * 2.0)
              +
              uTime * uSpeed
            );

          float wave =
            sin(
              centered.x *
              (5.0 + uFrequency * 4.0)
              +
              n * 4.0
              -
              uTime * uSpeed * 3.0
            );

          float band =
            smoothstep(
              1.0 - uBandWidth,
              1.0,
              abs(wave)
            );

          float glow =
            smoothstep(
              0.8,
              0.0,
              abs(
                centered.y +
                wave * 0.25
              )
            );

          float noiseAmount =
            mix(
              1.0,
              n,
              uNoise
            );

          float strength =
            (band * 0.35 +
             glow * 0.65)
            *
            noiseAmount
            *
            uIntensity;

          float verticalFade =
            smoothstep(
              0.0,
              uFadeTop,
              uv.y
            );

          strength *=
            mix(
              1.0,
              0.35,
              verticalFade
            );

          vec3 finalColor =
            uColor * strength;

          gl_FragColor =
            vec4(
              finalColor,
              strength * 0.72
            );
        }
      `,

      transparent: true,
      depthWrite: false,
    });

    const geometry =
      new THREE.PlaneGeometry(2, 2);

    const mesh =
      new THREE.Mesh(
        geometry,
        material
      );

    scene.add(mesh);

    const clock =
      new THREE.Clock();

    let animationFrame;

    function animate() {
      const elapsed =
        clock.getElapsedTime();

      uniforms.uTime.value =
        elapsed;

      renderer.render(
        scene,
        camera
      );

      animationFrame =
        requestAnimationFrame(
          animate
        );
    }

    animate();

    function handleResize() {
      const width =
        container.clientWidth;

      const height =
        container.clientHeight;

      renderer.setSize(
        width,
        height
      );
    }

    window.addEventListener(
      "resize",
      handleResize
    );

    return () => {
      cancelAnimationFrame(
        animationFrame
      );

      window.removeEventListener(
        "resize",
        handleResize
      );

      geometry.dispose();
      material.dispose();
      renderer.dispose();

      if (
        renderer.domElement.parentNode
      ) {
        renderer.domElement.parentNode.removeChild(
          renderer.domElement
        );
      }
    };
  }, [
    color,
    speed,
    frequency,
    noise,
    bandWidth,
    rotation,
    fadeTop,
    iterations,
    intensity,
  ]);

  return (
    <div
      ref={containerRef}
      className="color-bends"
    />
  );
}

export { ColorBends };
export default ColorBends;