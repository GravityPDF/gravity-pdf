import React from 'react'

const FontListSkeleton = () => {
  const fontList = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h']

  return (
    <div className='font-list-items-skeleton'>
      {fontList.map(font => (
        <div key={font} className='font-list-item'>
          <div>
            <span className='placeholder dashicons dashicons-trash' />
          </div>
          <span className='placeholder font-name' />
          <div>
            <span className='placeholder dashicons dashicons-yes' />
          </div>
          <div>
            <span className='placeholder dashicons dashicons-no-alt' />
          </div>
          <div>
            <span className='placeholder dashicons dashicons-no-alt' />
          </div>
          <div>
            <span className='placeholder dashicons dashicons-no-alt' />
          </div>
        </div>
      ))}
    </div>
  )
}

export default FontListSkeleton
